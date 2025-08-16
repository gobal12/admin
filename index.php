<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #EFEFEF;
        }
        /* Supaya card lebih ramping di layar kecil */
        @media (max-width: 576px) {
            .card {
                margin: 20px;
            }
            img {
                max-height: 120px;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-xl-10 col-lg-12 col-md-9">
            <div class="card o-hidden border-0 shadow-lg my-5">
                <div class="card-body p-0">
                    <div class="row">
                        <!-- Bagian Logo -->
                        <div class="col-12 col-md-6 d-flex justify-content-center align-items-center p-4 bg-white">
                            <img src="https://nutech-integrasi.com/wp-content/uploads/2019/09/Logo-Nutech-ok.png" 
                                 alt="Logo Nutech" 
                                 class="img-fluid" 
                                 style="max-width: 250px;">
                        </div>
                        <!-- Bagian Form -->
                        <div class="col-12 col-md-6">
                            <div class="p-5">
                                <div class="text-center">
                                    <h1 class="h4 text-gray-900 mb-4"><b>LOGIN</b></h1>
                                </div>
                                <form class="user" action="cek_login.php" method="post">
                                    <div class="form-group">
                                        <input type="email" 
                                               class="form-control form-control-user" 
                                               id="Email" 
                                               name="Email" 
                                               placeholder="Enter Email Address..." 
                                               required>
                                    </div>
                                    <div class="form-group">
                                        <input type="password" 
                                               class="form-control form-control-user" 
                                               id="Password" 
                                               name="Password" 
                                               placeholder="Password" 
                                               required>
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-user btn-block">Login</button>
                                </form>
                                <hr>
                                <?php
                                session_start();
                                if (isset($_SESSION['error_message'])) {
                                    echo '<div class="alert alert-danger">' . htmlspecialchars($_SESSION['error_message']) . '</div>';
                                    unset($_SESSION['error_message']);
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
