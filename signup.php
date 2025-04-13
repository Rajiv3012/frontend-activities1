<?php
session_start();
require 'vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require_once 'config.php'; // Use require_once to avoid redefinition

// Database Connection
$con = mysqli_connect('localhost', 'root', '', 'e waste', 3306);
if (!$con) {
    die("Connection failed: " . mysqli_connect_error());
}

// Handle OTP Sending
if (isset($_POST['send_otp'])) {
    $_SESSION['name'] = $_POST['name'];
    $_SESSION['email'] = $_POST['email'];
    $_SESSION['phone'] = $_POST['phone'];
    $_SESSION['dob'] = $_POST['dob'];
    $_SESSION['pincode'] = $_POST['pincode'];
    $_SESSION['city'] = $_POST['city'];
    $_SESSION['state'] = $_POST['state'];
    $_SESSION['password'] = $_POST['password'];
    $_SESSION['confirm_password'] = $_POST['confirm_password'];
    $_SESSION['terms'] = isset($_POST['terms']) ? 1 : 0;
    $email = $_POST['email'];
    $_SESSION['email'] = $email;
    $_SESSION['otp_time'] = time();

    $otp = rand(100000, 999999);
    $_SESSION['otp'] = password_hash($otp, PASSWORD_DEFAULT);

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USER;
        $mail->Password = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom(SMTP_USER, 'E Waste Collection');
        $mail->addAddress($email);
        $mail->Subject = "Your OTP for Signup";
        $mail->Body = "Your OTP is: " . $otp;

        $mail->send();
        $_SESSION['otp_sent'] = "OTP sent successfully!";
    } catch (Exception $e) {
        $_SESSION['error'] = "OTP sending failed: " . $mail->ErrorInfo;
    }
}

// Handle Signup
if (isset($_POST['signup'])) {
    if (!isset($_POST['terms'])) {
        $_SESSION['error'] = "You must agree to the Terms and Conditions!";
    }

    $firstname = htmlspecialchars(trim($_POST['name']));
    $email = htmlspecialchars(trim($_POST['email']));
    $phone = trim($_POST['phone']);
    $dob = $_POST['dob'];
    $pincode = trim($_POST['pincode']);
    $city = trim($_POST['city']);
    $state = trim($_POST['state']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $otp_entered = $_POST['otp'];

    if (time() - $_SESSION['otp_time'] > 300) {
        $_SESSION['error'] = "OTP expired!";
        header("Location: signup.php");
        exit();
    } elseif (!password_verify($otp_entered, $_SESSION['otp'])) {
        $_SESSION['error'] = "Invalid OTP!";
        header("Location: signup.php");
        exit();
    }

    $_SESSION['name'] = $firstname;
    $_SESSION['email'] = $email;
    $_SESSION['phone'] = $phone;
    $_SESSION['dob'] = $dob;
    $_SESSION['pincode'] = $pincode;
    $_SESSION['city'] = $city;
    $_SESSION['state'] = $state;
    $_SESSION['password'] = $password;
    $_SESSION['confirm_password'] = $confirm_password;

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Invalid email format!";
    } elseif ($password !== $confirm_password) {
        $_SESSION['error'] = "Passwords do not match!";
    } else {
        $stmt = mysqli_prepare($con, "SELECT * FROM `customer` WHERE email=?");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result) > 0) {
            $_SESSION['error'] = "Email is already registered!";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = mysqli_prepare($con, "INSERT INTO `customer` (fname, email, phone, dob, pincode, city, state, password) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "ssssssss", $firstname, $email, $phone, $dob, $pincode, $city, $state, $hashed_password);

            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['success'] = "Signup Successful! Please login.";
                unset($_SESSION['name'], $_SESSION['email'], $_SESSION['phone'], $_SESSION['dob'], $_SESSION['pincode'], $_SESSION['city'], $_SESSION['state'], $_SESSION['password'], $_SESSION['confirm_password']);
                header("Location: signin.php");
                exit();
            } else {
                $_SESSION['error'] = "Signup failed. Please try again!";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Signup - E Waste Collection</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root {
            --primary: #4CAF50;
            --secondary: #2E7D32;
            --accent: #8BC34A;
            --light: #F1F8E9;
            --dark: #1B5E20;
            --text: #333;
            --text-light: #666;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            transition: all 0.3s ease;
        }

        html {
            scroll-behavior: smooth;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            color: var(--text);
            line-height: 1.6;
            background-color: #f9f9f9;
        }
        
        .container {
            width: 90%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
        }
        
        /* Header Styles */
        header {
            background-color: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
        }
        
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 0;
        }
        
        .logo {
            font-size: 24px;
            font-weight: 700;
            color: var(--primary);
            text-decoration: none;
            display: flex;
            align-items: center;
        }
        
        .logo i {
            margin-right: 10px;
            color: var(--secondary);
        }
        
        .nav-links {
            display: flex;
            list-style: none;
        }
        
        .nav-links li {
            margin-left: 30px;
        }
        
        .nav-links a {
            text-decoration: none;
            color: var(--text);
            font-weight: 500;
            position: relative;
        }
        
        .nav-links a:hover {
            color: var(--primary);
        }
        
        .nav-links a::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            background: var(--primary);
            bottom: -5px;
            left: 0;
            transition: width 0.3s ease;
        }
        
        .nav-links a:hover::after {
            width: 100%;
        }
        
        .auth-buttons .btn {
            margin-left: 15px;
        }
        
        .btn {
            display: inline-block;
            padding: 10px 20px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 500;
            cursor: pointer;
        }
        
        .btn-outline {
            border: 2px solid var(--primary);
            color: var(--primary);
            background: transparent;
        }
        
        .btn-outline:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(76, 175, 80, 0.3);
        }
        
        .btn-solid {
            background: var(--primary);
            color: white;
            border: 2px solid var(--primary);
        }
        
        .btn-solid:hover {
            background: var(--secondary);
            border-color: var(--secondary);
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(76, 175, 80, 0.3);
        }
        
        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: var(--text);
        }
        
        /* Hero Section */
        .hero {
            padding: 150px 0 100px;
            background: linear-gradient(135deg, var(--light) 0%, white 100%);
            text-align: center;
        }
        
        .hero h1 {
            font-size: 3rem;
            margin-bottom: 20px;
            color: var(--dark);
        }
        
        .hero p {
            font-size: 1.2rem;
            color: var(--text-light);
            max-width: 700px;
            margin: 0 auto 40px;
        }
        
        .hero-buttons {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-bottom: 50px;
        }
        
        .hero-image {
            max-width: 800px;
            margin: 0 auto;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            transform: perspective(1000px) rotateX(5deg);
        }
        
        .hero-image img {
            width: 100%;
            height: auto;
            display: block;
        }
        
        /* Features Section */
        .features {
            padding: 100px 0;
            background: white;
        }
        
        .section-title {
            text-align: center;
            margin-bottom: 60px;
        }
        
        .section-title h2 {
            font-size: 2.5rem;
            color: var(--dark);
            margin-bottom: 15px;
        }
        
        .section-title p {
            color: var(--text-light);
            max-width: 700px;
            margin: 0 auto;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
        }
        
        .feature-card {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
        }
        
        .feature-icon {
            width: 80px;
            height: 80px;
            background: var(--light);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: var(--primary);
            font-size: 30px;
        }
        
        .feature-card h3 {
            font-size: 1.5rem;
            margin-bottom: 15px;
            color: var(--dark);
        }
        
        /* How It Works */
        .how-it-works {
            padding: 100px 0;
            background: var(--light);
        }
        
        .steps {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            margin-top: 50px;
        }
        
        .step {
            flex: 1;
            min-width: 250px;
            text-align: center;
            padding: 0 20px;
            position: relative;
            margin-bottom: 40px;
        }
        
        .step-number {
            width: 60px;
            height: 60px;
            background: var(--primary);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: 700;
            margin: 0 auto 20px;
            position: relative;
            z-index: 2;
        }
        
        .step:not(:last-child)::after {
            content: '';
            position: absolute;
            top: 30px;
            left: 50%;
            width: calc(100% - 60px);
            height: 2px;
            background: var(--primary);
            opacity: 0.3;
            z-index: 1;
        }
        
        .step h3 {
            font-size: 1.3rem;
            margin-bottom: 15px;
            color: var(--dark);
        }
        
        /* Testimonials */
        .testimonials {
            padding: 100px 0;
            background: white;
        }
        
        .testimonial-slider {
            max-width: 800px;
            margin: 0 auto;
            position: relative;
        }
        
        .testimonial {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
            text-align: center;
            margin: 20px;
            opacity: 0;
            transition: opacity 0.5s ease;
            position: absolute;
            width: calc(100% - 40px);
            top: 0;
            left: 0;
        }
        
        .testimonial.active {
            opacity: 1;
            position: relative;
        }
        
        .testimonial img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            margin: 0 auto 20px;
            border: 3px solid var(--light);
        }
        
        .testimonial p {
            font-style: italic;
            margin-bottom: 20px;
            color: var(--text-light);
        }
        
        .testimonial h4 {
            color: var(--dark);
        }
        
        .testimonial-rating {
            color: var(--accent);
            margin-bottom: 15px;
        }
        
        .slider-controls {
            display: flex;
            justify-content: center;
            margin-top: 30px;
        }
        
        .slider-dot {
            width: 12px;
            height: 12px;
            background: #ddd;
            border-radius: 50%;
            margin: 0 5px;
            cursor: pointer;
        }
        
        .slider-dot.active {
            background: var(--primary);
        }
        
        /* CTA Section */
        .cta {
            padding: 100px 0;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            text-align: center;
        }
        
        .cta h2 {
            font-size: 2.5rem;
            margin-bottom: 20px;
        }
        
        .cta p {
            max-width: 700px;
            margin: 0 auto 40px;
            opacity: 0.9;
        }
        
        .cta-buttons {
            display: flex;
            justify-content: center;
            gap: 20px;
        }
        
        .cta .btn-outline {
            border-color: white;
            color: white;
        }
        
        .cta .btn-outline:hover {
            background: white;
            color: var(--primary);
        }
        
        /* Footer */
        footer {
            background: var(--dark);
            color: white;
            padding: 60px 0 20px;
        }
        
        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 40px;
            margin-bottom: 40px;
        }
        
        .footer-column h3 {
            font-size: 1.2rem;
            margin-bottom: 20px;
            position: relative;
            padding-bottom: 10px;
        }
        
        .footer-column h3::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            width: 40px;
            height: 2px;
            background: var(--accent);
        }
        
        .footer-links {
            list-style: none;
        }
        
        .footer-links li {
            margin-bottom: 10px;
        }
        
        .footer-links a {
            color: #ddd;
            text-decoration: none;
        }
        
        .footer-links a:hover {
            color: var(--accent);
            padding-left: 5px;
        }
        
        .social-links {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }
        
        .social-links a {
            color: white;
            font-size: 20px;
        }
        
        .social-links a:hover {
            color: var(--accent);
            transform: translateY(-3px);
        }
        
        .footer-bottom {
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            color: #aaa;
            font-size: 0.9rem;
        }
        
        /* Responsive Styles */
        @media (max-width: 992px) {
            .hero h1 {
                font-size: 2.5rem;
            }
            
            .steps {
                flex-direction: column;
            }
            
            .step:not(:last-child)::after {
                top: 100%;
                left: 50%;
                width: 2px;
                height: 40px;
            }
        }
        
        @media (max-width: 768px) {
            .nav-links, .auth-buttons {
                display: none;
            }
            
            .mobile-menu-btn {
                display: block;
            }
            
            .hero {
                padding: 120px 0 80px;
            }
            
            .hero h1 {
                font-size: 2rem;
            }
            
            .hero-buttons {
                flex-direction: column;
                align-items: center;
            }
            
            .btn {
                width: 100%;
                max-width: 250px;
                margin-bottom: 15px;
            }
            
            .section-title h2 {
                font-size: 2rem;
            }
            
            .cta h2 {
                font-size: 2rem;
            }
            
            .cta-buttons {
                flex-direction: column;
                align-items: center;
            }
        }
        
        /* Animation Classes */
        .fade-in {
            animation: fadeIn 1s ease-in;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .slide-up {
            animation: slideUp 0.8s ease-out;
        }
        
        @keyframes slideUp {
            from { 
                opacity: 0;
                transform: translateY(50px);
            }
            to { 
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .pulse {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
    </style>
</head>
<body class="text-black">
<header>
        <div class="container">
            <nav class="navbar">
                 <a href="main.html" class="logo">
                    <i class="fas fa-recycle"></i>
                    ElectroBin
                </a> 

                <ul class="nav-links">
                    <li><a href="main.html">Home</a></li>
                    <li><a href="main.html">Features</a></li>
                    <li><a href="main.html">How It Works</a></li>
                    <li><a href="main.html">Testimonials</a></li>
                </ul>
                <div class="auth-buttons">
                    <a href="signin.php" class="btn btn-outline">Login</a>
                    <a href="signup.php" class="btn btn-solid">Sign Up</a>
                </div>
                <button class="mobile-menu-btn">
                    <i class="fas fa-bars"></i>
                </button>
            </nav>
        </div>
    </header>


<div class="flex justify-center items-center min-h-screen py-10">
    <div class="form-container p-8 w-full max-w-md">
        <h2 class="text-3xl font-bold text-center text-black mb-6 mt-8">Create an Account</h2>

        <?php
        if (isset($_SESSION['error'])) {
            echo "<p class='text-red-500 text-center mb-4'>".$_SESSION['error']."</p>";
            unset($_SESSION['error']);
        }
        if (isset($_SESSION['success'])) {
            echo "<p class='text-green-500 text-center mb-4'>".$_SESSION['success']."</p>";
            unset($_SESSION['success']);
        }
        if (isset($_SESSION['otp_sent'])) {
            echo "<p class='text-green-500 text-center mb-4'>".$_SESSION['otp_sent']."</p>";
            unset($_SESSION['otp_sent']);
        }
        ?>

        <form action="signup.php" method="POST" onsubmit="return validateTerms()" class="space-y-4">
            <div>
                <label class="block text-black font-semibold">Full Name</label>
                <input type="text" name="name" value="<?= isset($_SESSION['name']) ? htmlspecialchars($_SESSION['name']) : ''; ?>" required class="w-full px-4 py-2 border rounded-lg focus:ring focus:ring-green-300 text-black">
            </div>
            <div>
                <label class="block text-black font-semibold">Email</label>
                <input type="email" name="email" value="<?= isset($_SESSION['email']) ? htmlspecialchars($_SESSION['email']) : ''; ?>" required class="w-full px-4 py-2 border rounded-lg focus:ring focus:ring-green-300 text-black">
            </div>
            <div>
                <label class="block text-black font-semibold">Contact Number</label>
                <input type="tel" name="phone" pattern="[0-9]{10}" maxlength="10" value="<?= isset($_SESSION['phone']) ? htmlspecialchars($_SESSION['phone']) : ''; ?>" required class="w-full px-4 py-2 border rounded-lg focus:ring focus:ring-green-300 text-black" placeholder="Enter 10-digit number">
            </div>
            <div>
                <label class="block text-black font-semibold">Date of Birth</label>
                <input type="date" name="dob" value="<?= isset($_SESSION['dob']) ? htmlspecialchars($_SESSION['dob']) : ''; ?>" required class="w-full px-4 py-2 border rounded-lg focus:ring focus:ring-green-300 text-black">
            </div>
            <div>
                <label class="block text-black font-semibold">Pincode</label>
                <input type="text" id="pincode" name="pincode" required maxlength="6" pattern="[0-9]{6}" value="<?= isset($_SESSION['pincode']) ? htmlspecialchars($_SESSION['pincode']) : ''; ?>" class="w-full px-4 py-2 border rounded-lg focus:ring focus:ring-green-300 text-black" placeholder="Enter Pincode" onkeyup="fetchLocation()">
            </div>
            <div>
                <label class="block text-black font-semibold">City</label>
                <input type="text" id="city" name="city" value="<?= isset($_SESSION['city']) ? htmlspecialchars($_SESSION['city']) : ''; ?>" readonly required class="w-full px-4 py-2 border rounded-lg bg-gray-200 text-black">
            </div>
            <div>
                <label class="block text-black font-semibold">State</label>
                <input type="text" id="state" name="state" value="<?= isset($_SESSION['state']) ? htmlspecialchars($_SESSION['state']) : ''; ?>" readonly required class="w-full px-4 py-2 border rounded-lg bg-gray-200 text-black">
            </div>
            <div>
                <label class="block text-black font-semibold">Password</label>
                <input type="password" name="password" value="<?= isset($_SESSION['password']) ? htmlspecialchars($_SESSION['password']) : ''; ?>" required class="w-full px-4 py-2 border rounded-lg focus:ring focus:ring-green-300 text-black">
            </div>
            <div>
                <label class="block text-black font-semibold">Confirm Password</label>
                <input type="password" name="confirm_password" value="<?= isset($_SESSION['confirm_password']) ? htmlspecialchars($_SESSION['confirm_password']) : ''; ?>" required class="w-full px-4 py-2 border rounded-lg focus:ring focus:ring-green-300 text-black">
            </div>
            <div class="flex items-center">
                <input type="checkbox" id="terms" name="terms" class="mr-2">
                <label for="terms" class="text-black text-sm">I agree to the <a href="terms.php" class="text-green-500 underline">Terms and Conditions</a></label>
            </div>
            <button type="submit" name="send_otp" formnovalidate class="w-full bg-blue-500 text-white py-2 rounded-lg hover:bg-blue-600 transition duration-300">Send OTP</button>
            <div>
                <label class="block text-black font-semibold">Enter OTP</label>
                <input type="password" name="otp" maxlength="6" class="w-full px-4 py-2 border rounded-lg focus:ring focus:ring-green-300 text-black">
            </div>
            <button type="submit" name="signup" class="w-full bg-green-500 text-white py-2 rounded-lg hover:bg-green-600 transition duration-300">Sign Up</button>
            <p class="text-center text-black mt-3">Already have an account? <a href="signin.php" class="text-green-500 underline">Login</a></p>
        </form>
    </div>
</div>

<footer class="bg-navy-800 text-white p-4 text-center">
    <p>© 2025 E Waste Collection | <a href="https://www.instagram.com/" class="hover:text-pink-400">Instagram</a> | <a href="https://twitter.com/" class="hover:text-blue-400">Twitter</a></p>
</footer>

<script>
function fetchLocation() {
    let pincode = document.getElementById("pincode").value;
    let cityField = document.getElementById("city");
    let stateField = document.getElementById("state");

    if (pincode.length === 6) {
        fetch(`https://api.postalpincode.in/pincode/${pincode}`)
            .then(response => response.json())
            .then(data => {
                if (data[0].Status === "Success") {
                    cityField.value = data[0].PostOffice[0].District;
                    stateField.value = data[0].PostOffice[0].State;
                } else {
                    cityField.value = "";
                    stateField.value = "";
                    alert("Invalid Pincode! Please enter a valid one.");
                }
            })
            .catch(error => console.error("Error fetching location:", error));
    }
}

function validateTerms() {
    if (!document.getElementById("terms").checked) {
        alert("You must agree to the Terms and Conditions to sign up.");
        return false;
    }
    return true;
}

document.addEventListener("DOMContentLoaded", function () {
    const dropdownBtn = document.getElementById("dropdownBtn");
    const dropdownMenu = document.getElementById("dropdownMenu");
    const userDropdownBtn = document.getElementById("userDropdownBtn");
    const userDropdownMenu = document.getElementById("userDropdownMenu");
    const themeToggleBtn = document.getElementById("themeToggle");
    const body = document.body;

    if (dropdownBtn && dropdownMenu) {
        dropdownBtn.addEventListener("click", function (event) {
            event.stopPropagation();
            dropdownMenu.classList.toggle("hidden");
        });
        document.addEventListener("click", function (event) {
            if (!dropdownMenu.contains(event.target) && !dropdownBtn.contains(event.target)) {
                dropdownMenu.classList.add("hidden");
            }
        });
    }

    if (userDropdownBtn && userDropdownMenu) {
        userDropdownBtn.addEventListener("click", function (event) {
            event.stopPropagation();
            userDropdownMenu.classList.toggle("hidden");
        });
        document.addEventListener("click", function (event) {
            if (!userDropdownMenu.contains(event.target) && !userDropdownBtn.contains(event.target)) {
                userDropdownMenu.classList.add("hidden");
            }
        });
    }

    if (themeToggleBtn) {
        const savedTheme = localStorage.getItem("theme") || "dark";
        body.classList.toggle("bg-gray-100", savedTheme === "light");
        body.classList.toggle("bg-gray-900", savedTheme === "dark");
        body.classList.toggle("text-black", savedTheme === "light");
        body.classList.toggle("text-white", savedTheme === "dark");

        themeToggleBtn.addEventListener("click", function () {
            const newTheme = body.classList.contains("bg-gray-900") ? "light" : "dark";
            body.classList.toggle("bg-gray-100", newTheme === "light");
            body.classList.toggle("bg-gray-900", newTheme === "dark");
            body.classList.toggle("text-black", newTheme === "light");
            body.classList.toggle("text-white", newTheme === "dark");
            localStorage.setItem("theme", newTheme);
        });
    }
});
</script>
</body>
</html>