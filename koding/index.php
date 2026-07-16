<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Animasi Arsitektur Web Modern</title>

<style>
body{
    font-family:Arial, sans-serif;
    background:#0f172a;
    color:white;
    text-align:center;
    padding:20px;
}

h1{
    margin-bottom:30px;
}

.container{
    display:flex;
    flex-direction:column;
    align-items:center;
    gap:30px;
}

.row{
    display:flex;
    flex-wrap:wrap;
    justify-content:center;
    gap:20px;
}

.box{
    width:260px;
    padding:20px;
    border-radius:12px;
    box-shadow:0 8px 20px rgba(0,0,0,0.4);
    opacity:0;
    transform:translateY(50px);
    animation:fadeUp 1s forwards;
    transition:0.3s;
}

.box:hover{
    transform:scale(1.05);
}

.frontend{background:#2563eb; animation-delay:0.5s;}
.backend{background:#16a34a; animation-delay:1.5s;}
.database{background:#9333ea; animation-delay:2.5s;}

.arrow{
    font-size:40px;
    animation:moveArrow 1s infinite alternate;
}

@keyframes fadeUp{
    to{
        opacity:1;
        transform:translateY(0);
    }
}

@keyframes moveArrow{
    from{transform:translateY(0);}
    to{transform:translateY(10px);}
}

.desc{
    font-size:14px;
    line-height:1.5;
}
</style>

</head>

<body>

<h1>🚀 Animasi Arsitektur Teknologi Web</h1>

<div class="container">

<div class="row">
    <div class="box frontend">
        <h3>⚛ React.js</h3>
        <p class="desc">
        Library frontend untuk membuat UI interaktif berbasis komponen
        dan Single Page Application modern.
        </p>
    </div>

    <div class="box frontend">
        <h3>🟢 Vue.js</h3>
        <p class="desc">
        Framework frontend ringan dan fleksibel
        untuk membuat tampilan web dinamis dan responsif.
        </p>
    </div>
</div>

<div class="arrow">⬇️</div>

<div class="row">
    <div class="box backend">
        <h3>🔴 Laravel</h3>
        <p class="desc">
        Framework PHP backend untuk API,
        autentikasi, routing dan logika bisnis aplikasi.
        </p>
    </div>

    <div class="box backend">
        <h3>🟡 Node.js</h3>
        <p class="desc">
        Runtime JavaScript server-side
        untuk API cepat, realtime system
        dan microservices.
        </p>
    </div>
</div>

<div class="arrow">⬇️</div>

<div class="row">
    <div class="box database">
        <h3>🗄 SQL Database</h3>
        <p class="desc">
        Database relasional seperti MySQL/PostgreSQL
        untuk menyimpan dan mengelola data aplikasi.
        </p>
    </div>
</div>

</div>

</body>
</html>
