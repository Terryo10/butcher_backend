<!DOCTYPE html>
<html lang="en">
<head>
    <!--====== Required meta tags ======-->
    <meta charset="utf-8" />
    <meta http-equiv="x-ua-compatible" content="ie=edge" />
    <meta name="description" content="" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />

    <!--====== Title ======-->
    <title>Future+ App</title>

    <!--====== Favicon Icon ======-->
    <link rel="shortcut icon" href="assets/images/favicon.png" type="image/png" />

    <!--====== Bootstrap css ======-->
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />

    <!--====== Fontawesome css ======-->
    <link rel="stylesheet" href="assets/css/font-awesome.min.css" />

    <!--====== Magnific Popup css ======-->
    <link rel="stylesheet" href="assets/css/animate.min.css" />

    <!--====== Magnific Popup css ======-->
    <link rel="stylesheet" href="assets/css/magnific-popup.css" />

    <!--====== Slick css ======-->
    <link rel="stylesheet" href="assets/css/slick.css" />

    <!--====== Default css ======-->
    <link rel="stylesheet" href="assets/css/custom-animation.css" />
    <link rel="stylesheet" href="assets/css/default.css" />

    <!--====== Style css ======-->
    <link rel="stylesheet" href="assets/css/style.css" />

    <style>
        .appie-hero-content ul {
            list-style: none;
            padding: 0;
            display: flex;
            justify-content: center;
        }
        .appie-hero-content ul li {
            margin: 0 10px;
        }
        .appie-error-content p {
            margin-bottom: 5px; /* Adjusts the spacing below the paragraphs */
        }
        .appie-error-content h2 {
            margin-bottom: 10px; /* Adjusts the spacing below the referral code */
        }
    </style>
</head>

<body>
<!--====== PART START ======-->

<header class="appie-header-area appie-sticky">
    <div class="container">
        <div class="header-nav-box">
            <div class="row align-items-center">
                <div class="col-lg-2 col-md-4 col-sm-5 col-6 order-1 order-sm-1">
                    <div class="appie-logo-box">
                        <a href="index.html">
                            <img src="assets/images/logo.png" alt="" />
                        </a>
                    </div>
                </div>
                <div class="col-lg-6 col-md-1 col-sm-1 order-3 order-sm-2"></div>
                <div class="col-lg-4 col-md-7 col-sm-6 col-6 order-2 order-sm-3">
                    <div class="appie-btn-box text-right">
                        <a class="main-btn ml-30" href="index.html">About App</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>

<!--====== PART ENDS ======-->

<div class="appie-error-area">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="appie-error-content text-center">
                    <img src="/assets/images/error.png" alt="" width="400"/>
                    <h3 class="title">Hi, Referral to Yamba App</h3>
                    <p>Your friend {{$user->name}} has referred you to the Yamba App</p>
                    <p>Use code below as your sign up Referral</p>
                    <h2>{{$user->referral_code}}</h2>
                </div>
                <div class="appie-hero-content appie-hero-content-6">
                    <ul>
                        <li>
                            <a href="https://apps.apple.com/us/app/yamba-uganda/id6502561227"
                            ><i class="fab fa-apple"></i>
                                <span>Available on the <span>App Store</span></span></a
                            >
                        </li>
                        <li>
                            <a
                                class="item-2"
                                href="https://play.google.com/store/apps/details?id=com.un.yamba_app"
                            ><i class="fab fa-google-play"></i>
                                <span>Available on the <span>Google Play</span></span></a
                            >
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>



<!--====== APPIE BACK TO TOP PART ENDS ======-->
<div class="back-to-top">
    <a href="#"><i class="fal fa-arrow-up"></i></a>
</div>
<!--====== APPIE BACK TO TOP PART ENDS ======-->

<!--====== jquery js ======-->
<script src="assets/js/vendor/modernizr-3.6.0.min.js"></script>
<script src="assets/js/vendor/jquery-1.12.4.min.js"></script>

<!--====== Bootstrap js ======-->
<script src="assets/js/bootstrap.min.js"></script>
<script src="assets/js/popper.min.js"></script>

<!--====== wow js ======-->
<script src="assets/js/wow.js"></script>

<!--====== Slick js ======-->
<script src="assets/js/jquery.counterup.min.js"></script>
<script src="assets/js/waypoints.min.js"></script>

<!--====== TweenMax js ======-->
<script src="assets/js/TweenMax.min.js"></script>

<!--====== Slick js ======-->
<script src="assets/js/slick.min.js"></script>

<!--====== Magnific Popup js ======-->
<script src="assets/js/jquery.magnific-popup.min.js"></script>

<!--====== Main js ======-->
<script src="assets/js/main.js"></script>
</body>
</html>
