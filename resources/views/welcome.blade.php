<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vintage Meats</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #fdf5e6;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            height: 100vh;
            text-align: center;
        }
        h1 {
            color: #8b0000;
        }
        .buttons {
            margin-top: 20px;
        }
        .button {
            background-color: #8b0000;
            color: white;
            border: none;
            padding: 10px 20px;
            margin: 5px;
            font-size: 16px;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
        }
        .button:hover {
            background-color: #a52a2a;
        }
        .lottie-container {
            margin: 20px 0;
        }
    </style>
</head>
<body>

<h1>Welcome to Vintage Meats</h1>

<div class="lottie-container">
    <script src="https://unpkg.com/@dotlottie/player-component@2.7.12/dist/dotlottie-player.mjs" type="module"></script>
    <dotlottie-player
        src="https://lottie.host/390491c2-6d38-43ce-ab67-70cf15c87d41/2EFqo3dpOY.lottie"
        background="transparent"
        speed="1"
        style="width: 300px; height: 300px"
        loop
        autoplay>
    </dotlottie-player>
</div>

<div class="buttons">
    <a href="#" class="button">Download on App Store</a>
    <a href="#" class="button">Download on Huawei</a>
    <a href="#" class="button">Download on Android</a>
</div>

</body>
</html>
