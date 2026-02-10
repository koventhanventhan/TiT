<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redirecting to Login...</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .container {
            text-align: center;
            padding: 40px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            backdrop-filter: blur(10px);
        }
        .spinner {
            border: 4px solid rgba(255, 255, 255, 0.3);
            border-top: 4px solid white;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 20px auto;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        h1 {
            margin: 0 0 10px 0;
        }
        p {
            margin: 10px 0;
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Redirecting...</h1>
        <div class="spinner"></div>
        <p>Please login through the main website</p>
        <p>Redirecting to login page...</p>
    </div>
    <script>
        // Redirect to React frontend
        setTimeout(() => {
            window.location.href = 'http://localhost:3002?redirect=admin';
        }, 2000);
    </script>
</body>
</html>














