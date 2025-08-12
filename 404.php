<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Page Not Found | Project Management Tool</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .error-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
        }
        
        .error-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.1);
            text-align: center;
            max-width: 500px;
        }
        
        .error-icon {
            font-size: 6rem;
            color: #667eea;
            margin-bottom: 20px;
        }
        
        .error-title {
            font-size: 2.5rem;
            color: #333;
            margin-bottom: 15px;
        }
        
        .error-message {
            color: #666;
            font-size: 1.1rem;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        
        .error-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn-home {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-home:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }
        
        .btn-back {
            background: #6c757d;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-back:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }
        
        @media (max-width: 480px) {
            .error-card {
                padding: 30px 20px;
            }
            
            .error-title {
                font-size: 2rem;
            }
            
            .error-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-card">
            <div class="error-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            
            <h1 class="error-title">404</h1>
            
            <p class="error-message">
                Oops! The page you're looking for doesn't exist.<br>
                It might have been moved, deleted, or you entered the wrong URL.
            </p>
            
            <div class="error-actions">
                <a href="index.php" class="btn-home">
                    <i class="fas fa-home"></i> Go Home
                </a>
                <a href="javascript:history.back()" class="btn-back">
                    <i class="fas fa-arrow-left"></i> Go Back
                </a>
            </div>
        </div>
    </div>
</body>
</html>
