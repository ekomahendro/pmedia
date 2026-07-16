<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Development Process</title>

    <style>
        *{
            margin:0;
            padding:0;
            box-sizing:border-box;
        }

        body{
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            height:100vh;
            overflow:hidden;
            background: linear-gradient(135deg, #0f172a, #1e293b, #334155);
            display:flex;
            justify-content:center;
            align-items:center;
            color:white;
            position:relative;
        }

        /* Background Animation */
        .circle{
            position:absolute;
            border-radius:50%;
            background:rgba(255,255,255,0.05);
            animation: float 8s infinite ease-in-out;
        }

        .circle:nth-child(1){
            width:250px;
            height:250px;
            top:-60px;
            left:-60px;
        }

        .circle:nth-child(2){
            width:180px;
            height:180px;
            bottom:-50px;
            right:-50px;
            animation-delay:2s;
        }

        .circle:nth-child(3){
            width:120px;
            height:120px;
            bottom:120px;
            left:100px;
            animation-delay:4s;
        }

        @keyframes float{
            0%,100%{
                transform:translateY(0px);
            }
            50%{
                transform:translateY(-20px);
            }
        }

        .container{
            width:90%;
            max-width:700px;
            background:rgba(255,255,255,0.08);
            backdrop-filter: blur(10px);
            border:1px solid rgba(255,255,255,0.15);
            border-radius:25px;
            padding:50px 40px;
            text-align:center;
            box-shadow:0 15px 40px rgba(0,0,0,0.3);
            position:relative;
            z-index:2;
        }

        .icon{
            width:120px;
            height:120px;
            margin:auto;
            margin-bottom:25px;
            position:relative;
        }

        .gear{
            width:100px;
            height:100px;
            border:10px solid #38bdf8;
            border-radius:50%;
            margin:auto;
            position:relative;
            animation:spin 8s linear infinite;
        }

        .gear::before,
        .gear::after{
            content:'';
            position:absolute;
            background:#38bdf8;
        }

        .gear::before{
            width:140px;
            height:12px;
            top:34px;
            left:-30px;
        }

        .gear::after{
            width:12px;
            height:140px;
            top:-30px;
            left:34px;
        }

        @keyframes spin{
            from{
                transform:rotate(0deg);
            }
            to{
                transform:rotate(360deg);
            }
        }

        h1{
            font-size:42px;
            margin-bottom:15px;
            font-weight:700;
        }

        p{
            font-size:18px;
            color:#e2e8f0;
            line-height:1.8;
            margin-bottom:30px;
        }

        .progress-box{
            width:100%;
            background:rgba(255,255,255,0.15);
            border-radius:50px;
            overflow:hidden;
            height:22px;
            margin-bottom:15px;
        }

        .progress{
            width:70%;
            height:100%;
            background:linear-gradient(90deg,#38bdf8,#0ea5e9);
            border-radius:50px;
            animation:loading 3s infinite;
        }

        @keyframes loading{
            0%{
                width:25%;
            }
            50%{
                width:70%;
            }
            100%{
                width:40%;
            }
        }

        .status{
            font-size:14px;
            color:#cbd5e1;
            letter-spacing:1px;
        }

        .footer{
            margin-top:35px;
            font-size:13px;
            color:#94a3b8;
        }

        @media(max-width:600px){

            h1{
                font-size:30px;
            }

            p{
                font-size:16px;
            }

            .container{
                padding:40px 25px;
            }
        }
    </style>
</head>
<body>

    <!-- Background Graphics -->
    <div class="circle"></div>
    <div class="circle"></div>
    <div class="circle"></div>

    <div class="container">

        <div class="icon">
            <div class="gear"></div>
        </div>

        <h1>Website Sedang Dikembangkan</h1>

        <p>
            Halaman ini sedang dalam proses development dan peningkatan sistem.  
            Kami sedang menyiapkan tampilan serta fitur terbaik untuk Anda.
        </p>

        <div class="progress-box">
            <div class="progress"></div>
        </div>

        <div class="status">
            DEVELOPMENT IN PROGRESS...
        </div>

        <div class="footer">
            © 2026 All Rights Reserved
        </div>

    </div>

</body>
</html>