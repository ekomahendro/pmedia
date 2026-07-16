<?php
require_once '../../config.php';
require_once 'includes/pr_functions.php';

check_login();

$user_id = intval($_SESSION['user_id']);
$user_level = pr_get_level($user_id,$conn);

if($user_level==0){
    die("Access Denied");
}

/* ==========================================================
   PROFILE USER
========================================================== */

$q_me = mysqli_query($conn,"
SELECT
u.*,
d.dept_name,
d.dept_code
FROM htl_users u
LEFT JOIN htl_departments d
ON u.id_department=d.id_department
WHERE u.id_user=$user_id
LIMIT 1
");

$me = mysqli_fetch_assoc($q_me);

$my_dept_id = intval($me['id_department']);

$msg = '';
$msg_type = 'success';

/* ==========================================================
   CREATE PR
========================================================== */

if(isset($_POST['save_pr']))
{
    $pr_number = pr_generate_number($conn);

    $pr_date = mysqli_real_escape_string(
        $conn,
        $_POST['pr_date']
    );

    $description = mysqli_real_escape_string(
        $conn,
        $_POST['description']
    );

    $pr_type = mysqli_real_escape_string(
        $conn,
        $_POST['pr_type']
    );

    $dept_id = $my_dept_id;

    if(
        $user_level == 2 &&
        !empty($_POST['id_department'])
    ){
        $dept_id = intval($_POST['id_department']);
    }

    mysqli_query($conn,"
    INSERT INTO htl_pur_pr
    (
        pr_number,
        id_department,
        id_user_created,
        description,
        status_approval,
        app_dept_user_id,
        app_cc_user_id,
        status_cc,
        pr_date,
        pr_type
    )
    VALUES
    (
        '$pr_number',
        $dept_id,
        $user_id,
        '$description',
        'pending',
        0,
        0,
        'pending',
        '$pr_date',
        '$pr_type'
    )
    ");

    $new_id = mysqli_insert_id($conn);

    header("Location: pr_detail.php?id=".$new_id);
    exit;
}

/* ==========================================================
   APPROVE DEPT
========================================================== */

if(isset($_GET['approve_dept']))
{
    $id_pr = intval($_GET['approve_dept']);

    mysqli_query($conn,"
    UPDATE htl_pur_pr
    SET
        status_approval='approved',
        app_dept_user_id=$user_id
    WHERE id_pr=$id_pr
    ");

    header("Location: pr.php");
    exit;
}

/* ==========================================================
   APPROVE CC
========================================================== */

if(isset($_GET['approve_cc']))
{
    if($user_level != 2){
        die("Unauthorized");
    }

    $id_pr = intval($_GET['approve_cc']);

    mysqli_query($conn,"
    UPDATE htl_pur_pr
    SET
        status_cc='approved',
        app_cc_user_id=$user_id
    WHERE id_pr=$id_pr
    ");

    header("Location: pr.php");
    exit;
}

/* ==========================================================
   DELETE
========================================================== */

if(isset($_GET['delete']))
{
    $id_pr = intval($_GET['delete']);

    if(!pr_is_locked($id_pr,$conn))
    {
        mysqli_begin_transaction($conn);

        try{

            mysqli_query($conn,"
            DELETE FROM htl_pur_pr_detail
            WHERE id_pr=$id_pr
            ");

            mysqli_query($conn,"
            DELETE FROM htl_pur_pr
            WHERE id_pr=$id_pr
            ");

            mysqli_commit($conn);

        }catch(Exception $e){

            mysqli_rollback($conn);
        }
    }

    header("Location: pr.php");
    exit;
}

/* ==========================================================
   FILTER
========================================================== */

$where = '';

if($user_level == 1){
    $where = " WHERE p.id_department=$my_dept_id ";
}

if(!empty($_GET['search']))
{
    $s = mysqli_real_escape_string(
        $conn,
        $_GET['search']
    );

    $where .= ($where ? ' AND ' : ' WHERE ');
    $where .= "
    (
        p.pr_number LIKE '%$s%'
        OR
        p.description LIKE '%$s%'
    )
    ";
}

if(
    $user_level==2 &&
    !empty($_GET['filter_department'])
){
    $dept = intval($_GET['filter_department']);

    $where .= ($where ? ' AND ' : ' WHERE ');
    $where .= " p.id_department=$dept ";
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Purchase Request</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body{
    background:#f5f6f8;
}
</style>

</head>
<body>

<div class="container mt-4">

<div class="card shadow-sm mb-3">
<div class="card-body">

<h3 class="mb-3">
Purchase Request Management
</h3>

<form method="post">

<div class="row">

<div class="col-md-2">
<label>Tanggal</label>
<input
type="date"
name="pr_date"
class="form-control"
value="<?=date('Y-m-d')?>"
required>
</div>

<div class="col-md-2">
<label>Jenis PR</label>

<select
name="pr_type"
class="form-select"
required>

<option value="operational">
Operational
</option>

<option value="fixed_asset">
Fixed Asset
</option>

</select>
</div>

<?php if($user_level==2): ?>

<div class="col-md-3">

<label>Department</label>

<select
name="id_department"
class="form-select">

<?php

$dep = mysqli_query($conn,"
SELECT *
FROM htl_departments
ORDER BY dept_name
");

while($d=mysqli_fetch_assoc($dep))
{
?>

<option value="<?=$d['id_department']?>">

<?=$d['dept_name']?>

</option>

<?php } ?>

</select>

</div>

<?php endif; ?>

<div class="col-md-4">

<label>Keterangan</label>

<input
type="text"
name="description"
class="form-control"
required>

</div>

<div class="col-md-1">

<label>&nbsp;</label>

<button
type="submit"
name="save_pr"
class="btn btn-primary w-100">

Create

</button>

</div>

</div>

</form>

</div>
</div>

<div class="card shadow-sm mb-3">
<div class="card-body">

<form method="get">

<div class="row">

<div class="col-md-4">
<input
type="text"
name="search"
value="<?=htmlspecialchars($_GET['search'] ?? '')?>"
class="form-control"
placeholder="Search PR">
</div>

<?php if($user_level==2): ?>

<div class="col-md-3">

<select
name="filter_department"
class="form-select">

<option value="">
All Department
</option>

<?php

$dep = mysqli_query($conn,"
SELECT *
FROM htl_departments
ORDER BY dept_name
");

while($d=mysqli_fetch_assoc($dep))
{
?>

<option
value="<?=$d['id_department']?>">

<?=$d['dept_name']?>

</option>

<?php } ?>

</select>

</div>

<?php endif; ?>

<div class="col-md-2">
<button
class="btn btn-secondary w-100">
Filter
</button>
</div>

</div>

</form>

</div>
</div>

<div class="card shadow-sm">

<div class="table-responsive">

<table class="table table-bordered table-hover">

<thead class="table-dark">

<tr>

<th>No PR</th>
<th>Tanggal</th>
<th>Type</th>
<th>Department</th>
<th>Description</th>
<th>Total</th>
<th>Status</th>
<th width="260">Action</th>

</tr>

</thead>

<tbody>

<?php

$sql = mysqli_query($conn,"
SELECT
p.*,
d.dept_name
FROM htl_pur_pr p
LEFT JOIN htl_departments d
ON p.id_department=d.id_department
$where
ORDER BY p.id_pr DESC
");

while($row=mysqli_fetch_assoc($sql))
{

$total = pr_get_total(
    $row['id_pr'],
    $conn
);

?>

<tr>

<td><?=$row['pr_number']?></td>

<td><?=$row['pr_date']?></td>

<td><?=pr_type_badge($row['pr_type'])?></td>

<td><?=$row['dept_name']?></td>

<td><?=$row['description']?></td>

<td><?=rupiah($total)?></td>

<td>
<?=pr_status_badge(
    $row['status_approval'],
    $row['status_cc']
)?>
</td>

<td>

<a
href="pr_detail.php?id=<?=$row['id_pr']?>"
class="btn btn-sm btn-primary">

Detail

</a>

<?php
if(
$row['status_approval']!='approved'
){
?>

<a
href="?approve_dept=<?=$row['id_pr']?>"
class="btn btn-sm btn-success"
onclick="return confirm('Approve Dept?')">

Dept

</a>

<?php } ?>

<?php
if(
$user_level==2 &&
$row['status_approval']=='approved' &&
$row['status_cc']!='approved'
){
?>

<a
href="?approve_cc=<?=$row['id_pr']?>"
class="btn btn-sm btn-danger"
onclick="return confirm('Approve Cost Control?')">

CC

</a>

<?php } ?>

<?php
if(
!pr_is_locked(
$row['id_pr'],
$conn
)
){
?>

<a
href="?delete=<?=$row['id_pr']?>"
class="btn btn-sm btn-outline-danger"
onclick="return confirm('Delete PR?')">

Delete

</a>

<?php } ?>

</td>

</tr>

<?php } ?>

</tbody>

</table>

</div>

</div>

</div>

</body>
</html>