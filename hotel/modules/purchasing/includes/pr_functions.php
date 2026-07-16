<?php
function pr_generate_number($conn){
    return 'PR/'.date('Ym').'/'.str_pad(rand(1,9999),4,'0',STR_PAD_LEFT);
}
function pr_is_locked($id_pr,$conn){
    $q=mysqli_query($conn,"SELECT status_cc FROM htl_pur_pr WHERE id_pr=".(int)$id_pr);
    $r=mysqli_fetch_assoc($q);
    return strtolower($r['status_cc'] ?? '')=='approved';
}
?>
