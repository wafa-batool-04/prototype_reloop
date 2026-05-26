<?php
// header.php - Universal Header 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reloop Electronic Hub</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Poppins", Arial, sans-serif;
        }
        
        /* 3D GLASS CUBE LOGO STYLES */
        .logo-area {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .glass-cube-logo {
            position: relative;
            width: 50px;
            height: 50px;
            cursor: pointer;
            transition: transform 0.3s ease;
        }
        .glass-cube-logo:hover {
            transform: scale(1.05);
        }
        .cube-container {
            width: 100%;
            height: 100%;
            position: relative;
            perspective: 400px;
        }
        .rotating-cube {
            width: 100%;
            height: 100%;
            position: relative;
            transform-style: preserve-3d;
            animation: cubeSpin 8s infinite linear;
        }
        .cube-face {
            position: absolute;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: bold;
            backdrop-filter: blur(4px);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 6px;
        }
        .face-front {
            background: linear-gradient(135deg, #d8ee68, #375113);
            transform: translateZ(25px);
            box-shadow: 0 0 15px rgba(216, 238, 104, 0.3);
        }
        .face-back {
            background: linear-gradient(135deg, #0a1f44, #1c1917);
            transform: rotateY(180deg) translateZ(25px);
        }
        .face-right {
            background: linear-gradient(135deg, #d8ee68, #b8af06);
            transform: rotateY(90deg) translateZ(25px);
        }
        .face-left {
            background: linear-gradient(135deg, #1c1917, #0a1f44);
            transform: rotateY(-90deg) translateZ(25px);
        }
        .face-top {
            background: linear-gradient(135deg, #eae5dc, #d8ee68);
            transform: rotateX(90deg) translateZ(25px);
        }
        .face-bottom {
            background: linear-gradient(135deg, #0a1f44, #000000);
            transform: rotateX(-90deg) translateZ(25px);
        }
        .cube-face span {
            font-size: 26px;
            font-weight: bold;
            text-shadow: 0 1px 3px rgba(0,0,0,0.2);
        }
        .face-front span { color: #0a1f44; }
        .face-back span { color: #d8ee68; opacity: 0.8; }
        .face-right span { color: #0a1f44; }
        .face-left span { color: #d8ee68; opacity: 0.8; }
        .face-top span { color: #0a1f44; }
        .face-bottom span { color: #d8ee68; opacity: 0.6; }
        
        @keyframes cubeSpin {
            0% { transform: rotateX(0deg) rotateY(0deg); }
            25% { transform: rotateX(90deg) rotateY(90deg); }
            50% { transform: rotateX(180deg) rotateY(180deg); }
            75% { transform: rotateX(270deg) rotateY(270deg); }
            100% { transform: rotateX(360deg) rotateY(360deg); }
        }
        
        /* Glowing orbs */
        .glow-orb {
            position: absolute;
            border-radius: 50%;
            background: #d8ee68;
            opacity: 0;
            animation: orbFloat 4s infinite;
            pointer-events: none;
        }
        .orb1 { width: 4px; height: 4px; top: -5px; left: -5px; animation-delay: 0s; }
        .orb2 { width: 3px; height: 3px; top: -5px; right: -5px; animation-delay: 0.8s; }
        .orb3 { width: 3px; height: 3px; bottom: -5px; left: -5px; animation-delay: 1.6s; }
        .orb4 { width: 4px; height: 4px; bottom: -5px; right: -5px; animation-delay: 2.4s; }
        
        @keyframes orbFloat {
            0% { opacity: 0; transform: scale(0); }
            50% { opacity: 1; transform: scale(1.5); box-shadow: 0 0 10px #d8ee68; }
            100% { opacity: 0; transform: scale(0); }
        }
        
        .brand-text h1 {
            font-size: 24px;
            margin: 0;
            background: linear-gradient(135deg, #d8ee68, #eae5dc, #d8ee68);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            letter-spacing: 2px;
            font-weight: 700;
        }
        .brand-text p {
            font-size: 9px;
            margin: 2px 0 0;
            color: #b8af06;
            letter-spacing: 3px;
            font-weight: 600;
            text-transform: uppercase;
        }
    </style>
</head>
<body>