<?php
// inc/functions.php - numerical functions & simulation (no DB dependency)

function integrate_trap($func, $a, $b, $n=10000) {
    if ($b <= $a) return 0.0;
    $h = ($b - $a) / $n;
    $s = 0.5 * ($func($a) + $func($b));
    for ($i=1; $i<$n; $i++) {
        $x = $a + $i * $h;
        $s += $func($x);
    }
    return $s * $h;
}

function bisection($f, $a, $b, $tol=1e-6, $maxIter=100) {
    $fa = $f($a); $fb = $f($b);
    if (!is_numeric($fa) || !is_numeric($fb) || $fa * $fb > 0) return null;
    $i=0;
    while (($b - $a) / 2 > $tol && $i < $maxIter) {
        $c = ($a + $b) / 2;
        $fc = $f($c);
        if ($fc == 0) return $c;
        if ($fa * $fc < 0) {
            $b = $c; $fb = $fc;
        } else {
            $a = $c; $fa = $fc;
        }
        $i++;
    }
    return ($a + $b) / 2;
}

// compute EAC
function compute_eac($Pm, $Pmax, $Pmax_fault, $delta1_assumed=null) {
    if ($Pmax <= 0) return array('error'=>'Pmax harus > 0');
    if (abs($Pm) > $Pmax) return array('error'=>'|Pm| > Pmax (tidak valid)');
    $delta0 = asin($Pm / $Pmax);
    $res = array('delta0'=>$delta0);

    if ($delta1_assumed !== null && $delta1_assumed !== '') {
        if ($delta1_assumed <= $delta0) {
            $res['error']='delta1 harus > delta0 untuk percepatan';
            return $res;
        }
        $A1 = integrate_trap(function($d) use ($Pm, $Pmax_fault) {
            return max(0, $Pm - $Pmax_fault * sin($d));
        }, $delta0, $delta1_assumed);
        $A2max = integrate_trap(function($d) use ($Pm, $Pmax) {
            return max(0, $Pmax * sin($d) - $Pm);
        }, $delta1_assumed, pi());

        $res['delta1']=$delta1_assumed;
        $res['A1']=$A1;
        $res['A2max']=$A2max;
        $res['is_stable'] = ($A2max >= $A1) ? 1 : 0;

        if ($res['is_stable']) {
            $f = function($d) use ($Pm, $Pmax, $delta1_assumed, $A1) {
                $val = integrate_trap(function($x) use ($Pm, $Pmax) {
                    return $Pmax * sin($x) - $Pm;
                }, $delta1_assumed, $d);
                return $val - $A1;
            };
            $root = bisection($f, $delta1_assumed, pi()-1e-6);
            $res['delta_max']=$root;
            if ($root !== null) {
                $A2 = integrate_trap(function($d) use ($Pm, $Pmax) {
                    return max(0, $Pmax * sin($d) - $Pm);
                }, $delta1_assumed, $root);
                $res['A2']=$A2;
            }
        }
    }

    // critical clearing angle
    $f_cr = function($d) use ($Pm, $Pmax, $Pmax_fault) {
        if ($d <= 0) return 1;
        $delta0 = asin($Pm / $Pmax);
        $A1 = integrate_trap(function($x) use ($Pm, $Pmax_fault) { return max(0, $Pm - $Pmax_fault * sin($x)); }, $delta0, $d);
        $A2max = integrate_trap(function($x) use ($Pm, $Pmax) { return max(0, $Pmax * sin($x) - $Pm); }, $d, pi());
        return $A2max - $A1;
    };
    $fa = $f_cr(asin($Pm/$Pmax) + 1e-6);
    $fb = $f_cr(pi() - 1e-6);
    if (is_numeric($fa) && is_numeric($fb) && $fa * $fb <= 0) {
        $delta_cr = bisection($f_cr, asin($Pm/$Pmax) + 1e-6, pi() - 1e-6);
        $res['delta_cr']=$delta_cr;
    } else {
        $res['delta_cr']=null;
    }

    return $res;
}

// simulate SMIB RK4
function simulate_smib_rk4($Pm, $Pmax, $Pmax_fault, $delta0, $H=5.0, $f=50.0, $tmax=5.0, $dt=0.002, $fault_duration=0.2) {
    $ws = 2 * pi() * $f;
    $M = 2 * $H / $ws;
    $delta = $delta0;
    $omega = 0.0;
    $nsteps = (int)ceil($tmax / $dt);
    $t = 0.0;
    $data = array();
    for ($i=0; $i<=$nsteps; $i++) {
        $Pmax_cur = ($t <= $fault_duration) ? $Pmax_fault : $Pmax;
        $Pe = $Pmax_cur * sin($delta);
        $acc = ($Pm - $Pe) / $M;
        $k1d = $omega; $k1w = $acc;
        $d2 = $delta + 0.5 * $dt * $k1d; $w2 = $omega + 0.5 * $dt * $k1w;
        $Pcur2 = ($t + 0.5*$dt <= $fault_duration) ? $Pmax_fault : $Pmax;
        $acc2 = ($Pm - $Pcur2 * sin($d2)) / $M; $k2d = $w2; $k2w = $acc2;
        $d3 = $delta + 0.5 * $dt * $k2d; $w3 = $omega + 0.5 * $dt * $k2w;
        $Pcur3 = ($t + 0.5*$dt <= $fault_duration) ? $Pmax_fault : $Pmax;
        $acc3 = ($Pm - $Pcur3 * sin($d3)) / $M; $k3d = $w3; $k3w = $acc3;
        $d4 = $delta + $dt * $k3d; $w4 = $omega + $dt * $k3w;
        $Pcur4 = ($t + $dt <= $fault_duration) ? $Pmax_fault : $Pmax;
        $acc4 = ($Pm - $Pcur4 * sin($d4)) / $M; $k4d = $w4; $k4w = $acc4;
        $delta_next = $delta + ($dt/6.0) * ($k1d + 2*$k2d + 2*$k3d + $k4d);
        $omega_next = $omega + ($dt/6.0) * ($k1w + 2*$k2w + 2*$k3w + $k4w);
        $data[] = array('t'=>round($t,6), 'delta'=>$delta, 'omega'=>$omega);
        $delta = $delta_next; $omega = $omega_next; $t += $dt;
        if (abs($delta) > 50) break;
    }
    return array('data'=>$data, 'dt'=>$dt, 'H'=>$H);
}
?>
