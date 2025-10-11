<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/database_functions.php';

$database = new Database();
$db = $database->getConnection();
$carDB = new CarDetailingDB($db);

// Get services for public display
try {
    $services = $carDB->getServices();
    $dbStatus = "DB Connected - Services loaded: " . count($services);
} catch (Exception $e) {
    $services = [];
    $dbStatus = "DB Error: " . $e->getMessage();
}

// Add fallback services if database is empty - Complete 13 Services
if (empty($services)) {
    $services = [
        [
            'service_name' => 'Ceramic Coating (1-year Protection)',
            'description' => 'Premium nano-ceramic coating with 1-year protection guarantee',
            'price_small' => 4999
        ],
        [
            'service_name' => 'Engine Bay Cleaning',
            'description' => 'Professional engine compartment degreasing and detailing',
            'price_small' => 1499
        ],
        [
            'service_name' => 'Glass Polishing',
            'description' => 'Crystal-clear glass restoration and water repellent treatment',
            'price_small' => 899
        ],
        [
            'service_name' => 'Headlight Oxidation Removal',
            'description' => 'Restore clarity and brightness to oxidized headlights',
            'price_small' => 799
        ],
        [
            'service_name' => 'Odor Elimination (Ozone Treatment)',
            'description' => 'Advanced ozone treatment for complete odor elimination',
            'price_small' => 1299
        ],
        [
            'service_name' => 'Pet Hair Removal',
            'description' => 'Specialized pet hair extraction from all interior surfaces',
            'price_small' => 699
        ],
        [
            'service_name' => 'Upholstery or Leather Treatment',
            'description' => 'Professional cleaning and conditioning for all interior materials',
            'price_small' => 1599
        ],
        [
            'service_name' => 'Watermark and Acid Rain Removal (Full)',
            'description' => 'Complete removal of water spots and acid rain damage',
            'price_small' => 1899
        ],
        [
            'service_name' => 'Basic Exterior Care',
            'description' => 'Essential wash, dry, and basic protection service',
            'price_small' => 599
        ],
        [
            'service_name' => 'Express Care + Wax',
            'description' => 'Quick exterior wash with premium wax protection',
            'price_small' => 999
        ],
        [
            'service_name' => 'Full Exterior Detailing',
            'description' => 'Complete exterior restoration and protection package',
            'price_small' => 2999
        ],
        [
            'service_name' => 'Interior Deep Clean',
            'description' => 'Comprehensive interior cleaning and sanitization',
            'price_small' => 1999
        ],
        [
            'service_name' => 'Platinum Package (Full Interior + Exterior Detail)',
            'description' => 'Ultimate detailing experience - complete transformation',
            'price_small' => 7999
        ]
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ride Revive - Premium Car Detailing Services</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Apple's Exact Font System */
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@100;200;300;400;500;600;700;800;900&display=swap');
        
        /* Apple SF Pro Font Loading */
        @font-face {
            font-family: 'SF Pro Display';
            src: local('SF Pro Display'), local('SFProDisplay-Regular');
            font-weight: 400;
            font-display: swap;
        }
        
        @font-face {
            font-family: 'SF Pro Text';
            src: local('SF Pro Text'), local('SFProText-Regular');
            font-weight: 400;
            font-display: swap;
        }

        /* Apple's Signature Design Elements */
        :root {
            --accent-gold: #d4af37;
            --accent-gold-light: #e6c964;
            --accent-gold-dark: #b8941f;
            --bg-primary: #000000;
            --bg-secondary: #0d1117;
            --bg-tertiary: #161b22;
            --text-primary: #ffffff;
            --text-secondary: #c9d1d9;
            --text-tertiary: #6e7681;
            --apple-blue: #007AFF;
            --apple-gray: #8E8E93;
            
            /* Complementary colors for black-gold theme */
            --deep-blue: #1a1a2e;
            --midnight-blue: #16213e;
            --warm-white: #f8f8f8;
            --pearl-white: #f5f5f7;
            --champagne: #f7e7ce;
            --platinum: #e5e5e7;
            
            /* Apple's exact SF Pro typography system */
            --sf-pro-display: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'Inter', 'Helvetica Neue', Arial, sans-serif;
            --sf-pro-text: -apple-system, BlinkMacSystemFont, 'SF Pro Text', 'Inter', 'Helvetica Neue', Arial, sans-serif;
            
            /* Apple's exact typography scale from apple.com */
            --text-xs: 11px;
            --text-sm: 14px;
            --text-base: 17px;
            --text-lg: 21px;
            --text-xl: 28px;
            --text-2xl: 34px;
            --text-3xl: 48px;
            --text-4xl: 56px;
            --text-5xl: 76px;
            --text-6xl: 96px;
            --text-7xl: 120px;
            
            /* Apple spacing system */
            --space-xs: 4px;
            --space-sm: 8px;
            --space-md: 16px;
            --space-lg: 24px;
            --space-xl: 32px;
            --space-2xl: 48px;
            --space-3xl: 64px;
            --space-4xl: 80px;
            --space-5xl: 120px;
            
            /* Apple's exact line heights */
            --line-height-tight: 1.08333;
            --line-height-snug: 1.16667;
            --line-height-normal: 1.47059;
            --line-height-relaxed: 1.5;
            
            /* Apple's letter spacing */
            --letter-spacing-tight: -0.055em;
            --letter-spacing-normal: -0.022em;
            --letter-spacing-wide: -0.011em;
            
            /* Apple shadows */
            --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.12), 0 1px 2px rgba(0, 0, 0, 0.24);
            --shadow-md: 0 4px 6px rgba(0, 0, 0, 0.07), 0 2px 4px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px rgba(0, 0, 0, 0.1), 0 4px 6px rgba(0, 0, 0, 0.05);
            --shadow-xl: 0 20px 25px rgba(0, 0, 0, 0.1), 0 10px 10px rgba(0, 0, 0, 0.04);
            
            /* Apple border radius */
            --radius-sm: 6px;
            --radius-md: 12px;
            --radius-lg: 16px;
            --radius-xl: 24px;
            --radius-2xl: 32px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: var(--sf-pro-text);
            line-height: 1.47059;
            color: var(--text-primary);
            background: var(--bg-primary);
            margin: 0;
            padding: 0;
            overflow-x: hidden;
            scroll-behavior: smooth;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            font-feature-settings: "kern";
        }

        /* Apple's signature glass morphism */
        .glass-morphism {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        /* Apple's signature animations */
        @keyframes fadeInScale {
            from {
                opacity: 0;
                transform: scale(0.8) translateY(30px);
            }
            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(24px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes appleGlow {
            0%, 100% { 
                box-shadow: 0 0 30px rgba(212, 175, 55, 0.2), 
                           0 0 60px rgba(212, 175, 55, 0.1),
                           0 20px 40px rgba(0, 0, 0, 0.4);
            }
            50% { 
                box-shadow: 0 0 40px rgba(212, 175, 55, 0.4), 
                           0 0 80px rgba(212, 175, 55, 0.2),
                           0 25px 50px rgba(0, 0, 0, 0.6);
            }
        }

        @keyframes floatUpDown {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }

        /* Apple's content reveal animation */
        .fade-in-scale {
            animation: fadeInScale 1.2s cubic-bezier(0.16, 1, 0.3, 1) forwards;
            opacity: 0;
        }

        /* Apple's signature section spacing */
        .apple-section {
            padding: 120px 0;
            position: relative;
        }

        .apple-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(212, 175, 55, 0.3), transparent);
        }

        /* Apple's Signature Navigation Bar */
        .header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 10000;
            background: rgba(0, 0, 0, 0.72);
            backdrop-filter: saturate(180%) blur(20px);
            -webkit-backdrop-filter: saturate(180%) blur(20px);
            border-bottom: 0.5px solid rgba(255, 255, 255, 0.18);
            transition: all 0.3s cubic-bezier(0.28, 0, 0.63, 1);
            will-change: background-color, backdrop-filter;
        }

        .header.scrolled {
            background: rgba(0, 0, 0, 0.88);
            backdrop-filter: saturate(180%) blur(20px);
            -webkit-backdrop-filter: saturate(180%) blur(20px);
            border-bottom-color: rgba(255, 255, 255, 0.2);
        }

        .nav-container {
            max-width: 1024px;
            margin: 0 auto;
            padding: 0 var(--space-lg);
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 44px;
            position: relative;
        }

        .logo {
            font-size: var(--text-base);
            font-weight: 590;
            color: #f5f5f7;
            text-decoration: none;
            letter-spacing: var(--letter-spacing-normal);
            font-family: var(--sf-pro-display);
            transition: color 0.3s cubic-bezier(0.28, 0, 0.63, 1);
        }
        
        .logo:hover {
            color: var(--accent-gold);
        }

        .nav-links {
            display: flex;
            gap: var(--space-2xl);
            align-items: center;
        }

        .nav-link {
            color: rgba(245, 245, 247, 0.88);
            text-decoration: none;
            font-weight: 400;
            font-size: var(--text-base);
            transition: color 0.3s cubic-bezier(0.28, 0, 0.63, 1);
            position: relative;
            font-family: var(--sf-pro-text);
            letter-spacing: var(--letter-spacing-normal);
            padding: var(--space-sm) 0;
        }

        .nav-link:hover {
            color: #f5f5f7;
        }


        .cta-button {
            background: linear-gradient(135deg, var(--accent-gold) 0%, var(--accent-gold-light) 100%);
            color: #000;
            padding: var(--space-sm) var(--space-lg);
            border-radius: var(--radius-xl);
            text-decoration: none;
            font-weight: 500;
            font-size: var(--text-base);
            transition: all 0.2s cubic-bezier(0.28, 0, 0.63, 1);
            border: none;
            cursor: pointer;
            font-family: var(--sf-pro-text);
            letter-spacing: var(--letter-spacing-normal);
            box-shadow: none;
        }

        .cta-button:hover {
            transform: scale(1.02);
            background: linear-gradient(135deg, var(--accent-gold-light) 0%, #f4e4a6 100%);
        }
        
        /* Mobile Navigation */
        @media (max-width: 768px) {
            .nav-container {
                padding: 0 var(--space-md);
            }
            
            .nav-links {
                gap: var(--space-lg);
            }
            
            .nav-link {
                font-size: var(--text-sm);
            }
            
            .cta-button {
                padding: var(--space-xs) var(--space-md);
                font-size: var(--text-sm);
            }
        }
        
        @media (max-width: 480px) {
            .nav-links .nav-link:not(:last-child) {
                display: none;
            }
        }
        
        /* Apple-Style Split Showcase Section */
        .apple-showcase-section {
            padding: var(--space-5xl) 0;
            background: #ffffff;
            position: relative;
        }
        
        .showcase-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 var(--space-2xl);
        }
        
        .showcase-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: var(--space-4xl);
            align-items: center;
            min-height: 60vh;
        }
        
        .showcase-text {
            padding-right: var(--space-2xl);
        }
        
        .showcase-eyebrow {
            font-size: var(--text-sm);
            font-weight: 500;
            color: var(--accent-gold);
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: var(--space-md);
            font-family: var(--sf-pro-text);
        }
        
        .showcase-title {
            font-size: clamp(var(--text-3xl), 5vw, var(--text-5xl));
            font-weight: 600;
            color: #1d1d1f;
            line-height: var(--line-height-tight);
            letter-spacing: var(--letter-spacing-tight);
            margin-bottom: var(--space-lg);
            font-family: var(--sf-pro-display);
        }
        
        .showcase-description {
            font-size: var(--text-lg);
            color: #424245;
            line-height: var(--line-height-normal);
            margin-bottom: var(--space-2xl);
            font-family: var(--sf-pro-text);
            font-weight: 400;
            letter-spacing: var(--letter-spacing-wide);
        }
        
        .showcase-features {
            margin-bottom: var(--space-2xl);
        }
        
        .showcase-feature {
            display: flex;
            align-items: center;
            margin-bottom: var(--space-md);
        }
        
        .feature-check {
            width: 20px;
            height: 20px;
            background: var(--accent-gold);
            color: #000;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 600;
            margin-right: var(--space-md);
            flex-shrink: 0;
        }
        
        .showcase-feature span {
            font-size: var(--text-base);
            color: #424245;
            font-family: var(--sf-pro-text);
            font-weight: 400;
        }
        
        .showcase-cta {
            background: linear-gradient(135deg, var(--accent-gold) 0%, var(--accent-gold-light) 100%);
            color: #000;
            padding: var(--space-md) var(--space-2xl);
            border-radius: var(--radius-xl);
            text-decoration: none;
            font-weight: 500;
            font-size: var(--text-base);
            font-family: var(--sf-pro-text);
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
            display: inline-block;
        }
        
        .showcase-cta:hover {
            transform: scale(1.02);
            background: linear-gradient(135deg, var(--accent-gold-light) 0%, #f4e4a6 100%);
        }
        
        .showcase-media {
            position: relative;
        }
        
        .media-container {
            position: relative;
            border-radius: var(--radius-lg);
            overflow: hidden;
            aspect-ratio: 4/3;
        }
        
        .showcase-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }
        
        .media-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(0,0,0,0.1) 0%, rgba(0,0,0,0.3) 100%);
        }
        
        .showcase-media:hover .showcase-image {
            transform: scale(1.02);
        }
        
        @media (max-width: 768px) {
            .showcase-content {
                grid-template-columns: 1fr;
                gap: var(--space-2xl);
                text-align: center;
            }
            
            .showcase-text {
                padding-right: 0;
            }
        }
        
        /* 4-Grid Services Section */
        .services-grid-section {
            padding: 0;
            background: #000000;
        }
        
        .services-grid-container {
            max-width: 100vw;
            margin: 0;
            padding: 0;
        }
        
        
        .services-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 0;
        }
        
        .service-tile {
            position: relative;
            aspect-ratio: 4/3;
            overflow: hidden;
            cursor: pointer;
            min-height: 440px;
        }
        
        .tile-image {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
        }
        
        .tile-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }
        
        .tile-content {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(transparent, rgba(0,0,0,0.8));
            color: white;
            padding: var(--space-2xl) var(--space-lg) var(--space-lg);
            transform: translateY(60%);
            transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }
        
        .service-tile:hover .tile-content {
            transform: translateY(0);
        }
        
        .service-tile:hover .tile-img {
            transform: scale(1.05);
        }
        
        .tile-title {
            font-size: var(--text-xl);
            font-weight: 600;
            color: #ffffff;
            margin-bottom: var(--space-sm);
            font-family: var(--sf-pro-display);
        }
        
        .tile-description {
            font-size: var(--text-sm);
            color: rgba(255, 255, 255, 0.8);
            line-height: var(--line-height-normal);
            margin-bottom: var(--space-md);
            font-family: var(--sf-pro-text);
            opacity: 0;
            transform: translateY(10px);
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1) 0.1s;
        }
        
        .tile-price {
            font-size: var(--text-lg);
            font-weight: 600;
            color: var(--accent-gold);
            margin-bottom: var(--space-md);
            font-family: var(--sf-pro-text);
            opacity: 0;
            transform: translateY(10px);
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1) 0.2s;
        }
        
        .tile-cta {
            background: transparent;
            color: var(--accent-gold);
            border: 1px solid var(--accent-gold);
            padding: var(--space-xs) var(--space-md);
            border-radius: var(--radius-sm);
            text-decoration: none;
            font-weight: 500;
            font-size: var(--text-xs);
            font-family: var(--sf-pro-text);
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
            display: inline-block;
            opacity: 0;
            transform: translateY(10px);
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1) 0.3s;
        }
        
        .service-tile:hover .tile-description,
        .service-tile:hover .tile-price,
        .service-tile:hover .tile-cta {
            opacity: 1;
            transform: translateY(0);
        }
        
        .tile-cta:hover {
            background: var(--accent-gold);
            color: #000;
        }
        
        @media (max-width: 768px) {
            .services-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 480px) {
            .services-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Apple's Signature Hero Design */
        .fullscreen-hero {
            height: 100vh;
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            background: radial-gradient(ellipse 80% 50% at 50% -20%, rgba(212, 175, 55, 0.15), transparent),
                        radial-gradient(ellipse 60% 80% at 20% 100%, rgba(0, 122, 255, 0.1), transparent),
                        linear-gradient(180deg, #000000 0%, #0a0a0a 100%);
        }

        .hero-background {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1;
        }

        .hero-bg-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
            filter: brightness(0.8) contrast(1.15) saturate(1.1);
        }

        .hero-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(ellipse at center, rgba(0,0,0,0.2) 0%, rgba(0,0,0,0.8) 100%);
        }


        /* Apple's Signature Hero Content */
        .hero-content-wrapper {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 5;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 0 var(--space-2xl);
            max-width: 840px;
            width: 100%;
            text-align: center;
        }

        .hero-brand-text {
            font-size: var(--text-sm);
            font-weight: 500;
            color: rgba(245, 245, 247, 0.6);
            letter-spacing: 0.08em;
            margin-bottom: var(--space-md);
            font-family: var(--sf-pro-text);
            text-transform: uppercase;
            animation: fadeInUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) 0.2s both;
        }

        .massive-title {
            font-size: clamp(var(--text-4xl), 8vw, var(--text-6xl));
            font-weight: 600;
            color: var(--pearl-white);
            line-height: var(--line-height-tight);
            letter-spacing: var(--letter-spacing-tight);
            margin: 0 0 var(--space-xl) 0;
            font-family: var(--sf-pro-display);
            animation: fadeInUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) 0.4s both;
        }

        .massive-title::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 120%;
            height: 120%;
            background: radial-gradient(ellipse, rgba(212, 175, 55, 0.1) 0%, transparent 70%);
            transform: translate(-50%, -50%);
            z-index: -1;
            filter: blur(40px);
            animation: float 6s ease-in-out infinite;
        }

        .title-line {
            display: block;
        }

        .hero-subtitle-premium {
            font-size: clamp(var(--text-lg), 2.5vw, var(--text-xl));
            color: #a1a1a6;
            margin-bottom: var(--space-2xl);
            line-height: var(--line-height-normal);
            font-weight: 400;
            max-width: 540px;
            font-family: var(--sf-pro-text);
            letter-spacing: var(--letter-spacing-wide);
            animation: fadeInUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) 0.6s both;
        }

        .hero-cta-premium {
            display: flex;
            gap: var(--space-lg);
            flex-wrap: wrap;
            justify-content: center;
            animation: fadeInUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) 0.8s both;
        }

        .view-details-btn {
            background: linear-gradient(135deg, var(--accent-gold) 0%, var(--accent-gold-light) 100%);
            color: #000000;
            padding: var(--space-md) var(--space-xl);
            font-size: var(--text-base);
            font-weight: 500;
            border: none;
            border-radius: var(--radius-xl);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: var(--space-sm);
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
            cursor: pointer;
            font-family: var(--sf-pro-text);
            letter-spacing: var(--letter-spacing-normal);
            box-shadow: var(--shadow-md);
            position: relative;
            overflow: hidden;
            min-width: auto;
            justify-content: center;
        }

        .view-details-btn:hover {
            background: linear-gradient(135deg, var(--accent-gold-light) 0%, #f4e4a6 100%);
            transform: scale(1.02);
            box-shadow: 0 6px 25px rgba(212, 175, 55, 0.4);
        }

        .view-details-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.8s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .cta-primary-premium:hover::before {
            left: 100%;
        }

        .cta-primary-premium:hover {
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 8px 30px rgba(212, 175, 55, 0.6);
            background: linear-gradient(135deg, rgba(212, 175, 55, 0.95) 0%, var(--accent-gold) 100%);
        }

        .cta-secondary-premium {
            background: rgba(245, 245, 247, 0.1);
            color: rgba(245, 245, 247, 0.9);
            padding: 16px 32px;
            font-size: 17px;
            font-weight: 500;
            border: 1px solid rgba(245, 245, 247, 0.2);
            border-radius: 28px;
            text-decoration: none;
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            cursor: pointer;
            letter-spacing: -0.022em;
            font-family: var(--sf-pro-text);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
        }

        .cta-secondary-premium:hover {
            background: rgba(245, 245, 247, 0.15);
            border-color: rgba(212, 175, 55, 0.4);
            color: var(--accent-gold);
            transform: translateY(-2px);
            box-shadow: 0 6px 25px rgba(255, 255, 255, 0.1);
        }

        /* Apple's Signature Scroll Indicator */
        .scroll-indicator {
            position: absolute;
            bottom: 40px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 12px;
            color: rgba(245, 245, 247, 0.6);
            opacity: 0.8;
            animation: fadeInScale 2s cubic-bezier(0.16, 1, 0.3, 1) 1.2s both;
        }

        .scroll-text {
            font-size: 11px;
            font-weight: 590;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            font-family: var(--sf-pro-text);
            margin-bottom: 8px;
        }

        .scroll-arrow {
            width: 24px;
            height: 24px;
            border: 1px solid rgba(245, 245, 247, 0.4);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: float 3s ease-in-out infinite;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            font-size: 12px;
        }

        .scroll-arrow:hover {
            border-color: var(--accent-gold);
            background: rgba(212, 175, 55, 0.1);
            transform: scale(1.1);
            color: var(--accent-gold);
        }

        .scroll-arrow::after {
            content: '↓';
            color: inherit;
        }

        /* Location Badge */
        .location-badge {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 1000;
        }

        .badge-content {
            background: #fff;
            color: #000;
            padding: 10px 20px;
            border-radius: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
            font-size: 0.9rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .badge-dot {
            width: 8px;
            height: 8px;
            background: #00ff00;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }

        .location-badge {
            position: fixed;
            bottom: 25px;
            right: 25px;
            z-index: 1000;
        }

        .badge-content {
            background: rgba(17, 17, 17, 0.9);
            border: 1px solid rgba(212, 175, 55, 0.3);
            color: var(--text-primary);
            padding: 8px 16px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
            font-size: 0.8rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.4);
            backdrop-filter: blur(20px);
            font-family: var(--sf-pro-text);
        }

        .badge-dot {
            width: 6px;
            height: 6px;
            background: var(--accent-gold);
            border-radius: 50%;
            animation: pulse 2.5s cubic-bezier(0.25, 0.46, 0.45, 0.94) infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 0.8; transform: scale(1); }
            50% { opacity: 1; transform: scale(1.3); box-shadow: 0 0 10px var(--accent-gold); }
        }

        /* Secondary Hero Section */
        .hero {
            min-height: 100vh;
            background: #000;
            color: #fff;
            position: relative;
            overflow: hidden;
        }

        .hero-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            min-height: 100vh;
            max-width: 1400px;
            margin: 0 auto;
        }

        .hero-content {
            padding: 100px 60px 60px 60px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            z-index: 2;
        }

        .hero-badge {
            font-size: 0.9rem;
            font-weight: 600;
            color: rgba(255, 255, 255, 0.8);
            letter-spacing: 2px;
            margin-bottom: 20px;
        }

        .hero-title {
            font-size: clamp(2.5rem, 5vw, 4rem);
            font-weight: 900;
            line-height: 1.1;
            margin-bottom: 30px;
            letter-spacing: -0.02em;
        }

        .location-text {
            color: var(--accent-gold);
            display: block;
        }

        .hero-description {
            font-size: 1.1rem;
            line-height: 1.6;
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 40px;
            max-width: 500px;
        }

        .hero-contact {
            margin-bottom: 40px;
        }

        .contact-item {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
            font-size: 1rem;
            font-weight: 600;
        }

        .contact-item i {
            width: 20px;
            color: #fff;
        }

        .hero-buttons {
            display: flex;
            gap: 20px;
            margin-bottom: 40px;
        }

        .btn-maintenance {
            background: linear-gradient(135deg, var(--accent-gold) 0%, var(--accent-gold-light) 100%);
            color: #000;
            padding: var(--space-md) var(--space-xl);
            font-weight: 500;
            font-size: var(--text-base);
            letter-spacing: var(--letter-spacing-normal);
            text-decoration: none;
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
            border: none;
            cursor: pointer;
            border-radius: var(--radius-lg);
            font-family: var(--sf-pro-text);
            box-shadow: var(--shadow-md);
        }

        .btn-book {
            background: transparent;
            color: var(--accent-gold);
            padding: var(--space-md) var(--space-xl);
            font-weight: 500;
            font-size: var(--text-base);
            letter-spacing: var(--letter-spacing-normal);
            text-decoration: none;
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
            border: 1px solid var(--accent-gold);
            cursor: pointer;
            border-radius: var(--radius-lg);
            font-family: var(--sf-pro-text);
        }

        .btn-maintenance:hover {
            background: linear-gradient(135deg, var(--accent-gold-light) 0%, #f4e4a6 100%);
            transform: scale(1.02);
            box-shadow: var(--shadow-lg);
        }
        
        .btn-book:hover {
            background: var(--accent-gold);
            color: #000;
            transform: scale(1.02);
            box-shadow: var(--shadow-md);
        }

        .hero-images {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 60px;
        }

        .main-image {
            position: relative;
            z-index: 2;
            width: 100%;
            max-width: 400px;
        }

        .detailing-image {
            width: 100%;
            height: 300px;
            object-fit: cover;
            border-radius: 8px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5);
        }

        .secondary-image {
            position: absolute;
            top: 20px;
            right: 20px;
            width: 200px;
            z-index: 1;
        }

        .car-detail-image {
            width: 100%;
            height: 500px;
            object-fit: cover;
            border-radius: 8px;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.4);
        }

        /* Chat Widget */
        .chat-widget {
            position: fixed;
            bottom: 30px;
            left: 30px;
            z-index: 1000;
        }

        .chat-bubble {
            background: #ff4444;
            color: #fff;
            padding: 12px 20px;
            border-radius: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(255, 68, 68, 0.3);
        }

        .chat-bubble:hover {
            background: #e63939;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 68, 68, 0.4);
        }

        /* Location Badge */
        .location-badge {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 1000;
        }

        .badge-content {
            background: #fff;
            color: #000;
            padding: 10px 20px;
            border-radius: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
            font-size: 0.9rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .badge-dot {
            width: 8px;
            height: 8px;
            background: #22c55e;
            border-radius: 50%;
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.7; transform: scale(1.2); }
        }

        /* Apple-inspired Button Styles */
        .btn-primary {
            background: linear-gradient(135deg, var(--accent-gold) 0%, var(--accent-gold-light) 100%);
            color: var(--bg-primary);
            padding: 16px 32px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-family: var(--sf-pro-text);
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(212, 175, 55, 0.4);
            background: linear-gradient(135deg, var(--accent-gold-light) 0%, var(--accent-gold) 100%);
        }

        .btn-secondary {
            background: transparent;
            color: var(--text-primary);
            padding: 16px 32px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 500;
            font-size: 1rem;
            transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            border: 1px solid rgba(212, 175, 55, 0.3);
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-family: var(--sf-pro-text);
        }

        .btn-secondary:hover {
            background: rgba(212, 175, 55, 0.1);
            border-color: var(--accent-gold);
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(212, 175, 55, 0.2);
        }

        /* Hero Section enhancements */
        .hero-badge {
            background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
            color: #000;
            padding: 8px 20px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 20px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        .hero-stats {
            display: flex;
            justify-content: center;
            gap: 40px;
            margin: 30px 0 40px 0;
            flex-wrap: wrap;
        }

        .stat-item {
            text-align: center;
        }

        .stat-number {
            display: block;
            font-size: 1.8rem;
            font-weight: 800;
            color: #FFD700;
            line-height: 1;
        }

        .stat-label {
            display: block;
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.7);
            margin-top: 5px;
        }

        .hero-trust {
            margin-top: 20px;
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.6);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .hero-trust i {
            color: #22c55e;
        }

        /* Apple-inspired Problem/Solution Section */
        .problem-section {
            padding: 120px 40px;
            background: linear-gradient(180deg, var(--bg-primary) 0%, var(--bg-secondary) 50%, var(--bg-primary) 100%);
            border-top: 1px solid rgba(212, 175, 55, 0.1);
            border-bottom: 1px solid rgba(212, 175, 55, 0.1);
            position: relative;
        }

        .problem-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 50%;
            width: 1px;
            height: 100%;
            background: linear-gradient(180deg, transparent, var(--accent-gold), transparent);
            opacity: 0.3;
            transform: translateX(-50%);
        }

        .problem-content {
            max-width: 1200px;
            margin: 0 auto;
        }

        .problem-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 80px;
            align-items: start;
        }

        .problem-title, .solution-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 40px;
            text-align: center;
            font-family: var(--sf-pro-display);
            letter-spacing: -0.02em;
        }

        .problem-title {
            color: #ff6b6b;
            text-shadow: 0 0 20px rgba(255, 107, 107, 0.3);
        }

        .solution-title {
            color: var(--accent-gold);
            text-shadow: 0 0 20px rgba(212, 175, 55, 0.3);
        }

        .problem-item, .solution-item {
            display: flex;
            align-items: flex-start;
            gap: 20px;
            margin-bottom: 30px;
            padding: 25px;
            border-radius: 16px;
            transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            border: 1px solid transparent;
        }

        .problem-item {
            background: rgba(255, 107, 107, 0.03);
            border-color: rgba(255, 107, 107, 0.1);
        }

        .problem-item:hover {
            background: rgba(255, 107, 107, 0.06);
            transform: translateX(8px);
            border-color: rgba(255, 107, 107, 0.2);
            box-shadow: 0 10px 30px rgba(255, 107, 107, 0.1);
        }

        .solution-item {
            background: rgba(212, 175, 55, 0.03);
            border-color: rgba(212, 175, 55, 0.1);
        }

        .solution-item:hover {
            background: rgba(212, 175, 55, 0.06);
            transform: translateX(-8px);
            border-color: rgba(212, 175, 55, 0.2);
            box-shadow: 0 10px 30px rgba(212, 175, 55, 0.1);
        }

        .problem-item i {
            color: #ff6b6b;
            font-size: 1.3rem;
            margin-top: 2px;
            filter: drop-shadow(0 0 5px rgba(255, 107, 107, 0.3));
        }

        .solution-item i {
            color: var(--accent-gold);
            font-size: 1.3rem;
            margin-top: 2px;
            filter: drop-shadow(0 0 5px rgba(212, 175, 55, 0.3));
        }

        .problem-item h4, .solution-item h4 {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 8px;
            font-family: var(--sf-pro-display);
            letter-spacing: -0.01em;
        }

        .problem-item p, .solution-item p {
            font-size: 0.95rem;
            color: var(--text-secondary);
            line-height: 1.6;
            margin: 0;
            font-family: var(--sf-pro-text);
        }

        .solution-cta {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            background: linear-gradient(135deg, var(--accent-gold) 0%, var(--accent-gold-light) 100%);
            color: var(--bg-primary);
            padding: 16px 32px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            margin-top: 30px;
            border: 1px solid var(--accent-gold);
            font-family: var(--sf-pro-text);
        }

        .solution-cta:hover {
            transform: translateY(-4px);
            box-shadow: 0 15px 40px rgba(212, 175, 55, 0.4);
            background: linear-gradient(135deg, var(--accent-gold-light) 0%, var(--accent-gold) 100%);
        }

        /* Apple-Style Product Showcase Section */
        .apple-showcase-section {
            padding: 120px 0;
            background: #f5f5f7;
            background: linear-gradient(180deg, #f5f5f7 0%, #fbfbfd 100%);
        }

        .showcase-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 40px;
        }

        .showcase-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-bottom: 40px;
        }

        .showcase-grid.secondary {
            margin-bottom: 0;
        }

        .showcase-card {
            background: #ffffff;
            border-radius: 28px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            position: relative;
        }

        .showcase-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
        }

        .card-header {
            padding: 60px 60px 40px 60px;
            text-align: center;
        }

        .product-title {
            font-size: 48px;
            font-weight: 700;
            color: #1d1d1f;
            margin-bottom: 16px;
            letter-spacing: -0.055em;
            font-family: var(--sf-pro-display);
        }

        .product-subtitle {
            font-size: 24px;
            color: #86868b;
            margin-bottom: 32px;
            line-height: 1.33;
            font-weight: 400;
            letter-spacing: -0.022em;
            font-family: var(--sf-pro-text);
        }

        .card-actions {
            display: flex;
            gap: 16px;
            justify-content: center;
        }

        .apple-btn-primary {
            background: #0071e3;
            color: #ffffff;
            padding: 12px 24px;
            border-radius: 20px;
            text-decoration: none;
            font-size: 17px;
            font-weight: 400;
            letter-spacing: -0.022em;
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
            font-family: var(--sf-pro-text);
        }

        .apple-btn-primary:hover {
            background: #0077ed;
            transform: scale(1.02);
        }

        .apple-btn-secondary {
            background: transparent;
            color: #0071e3;
            padding: 12px 24px;
            border-radius: 20px;
            text-decoration: none;
            font-size: 17px;
            font-weight: 400;
            letter-spacing: -0.022em;
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
            font-family: var(--sf-pro-text);
            border: 1px solid transparent;
        }

        .apple-btn-secondary:hover {
            background: rgba(0, 113, 227, 0.04);
            transform: scale(1.02);
        }

        .card-visual {
            padding: 0 60px 60px 60px;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .showcase-image {
            width: 100%;
            max-width: 400px;
            height: auto;
            border-radius: 16px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
            transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .showcase-card:hover .showcase-image {
            transform: scale(1.02);
        }

        /* Secondary Cards */
        .secondary-card {
            display: flex;
            align-items: center;
            padding: 40px;
            min-height: 200px;
        }

        .secondary-content {
            flex: 1;
            padding-right: 40px;
        }

        .secondary-icon {
            color: #0071e3;
            font-size: 32px;
            margin-bottom: 16px;
        }

        .secondary-title {
            font-size: 28px;
            font-weight: 700;
            color: #1d1d1f;
            margin-bottom: 12px;
            letter-spacing: -0.022em;
            font-family: var(--sf-pro-display);
        }

        .secondary-text {
            font-size: 17px;
            color: #86868b;
            margin-bottom: 24px;
            line-height: 1.47;
            font-weight: 400;
            letter-spacing: -0.022em;
            font-family: var(--sf-pro-text);
        }

        .secondary-btn {
            background: #0071e3;
            color: #ffffff;
            padding: 8px 16px;
            border-radius: 16px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 400;
            letter-spacing: -0.016em;
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
            font-family: var(--sf-pro-text);
            display: inline-block;
            margin-right: 12px;
            margin-bottom: 8px;
        }

        .secondary-btn:hover {
            background: #0077ed;
            transform: scale(1.02);
        }

        .secondary-btn:not(.primary) {
            background: transparent;
            color: #0071e3;
            border: 1px solid #d2d2d7;
        }

        .secondary-btn:not(.primary):hover {
            background: rgba(0, 113, 227, 0.04);
            border-color: #0071e3;
        }

        .secondary-visual {
            flex: 0 0 150px;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .secondary-image {
            width: 120px;
            height: 90px;
            object-fit: cover;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .card-mockup {
            perspective: 1000px;
        }

        .membership-card {
            width: 100px;
            height: 60px;
            background: linear-gradient(135deg, #1d1d1f 0%, #424245 100%);
            border-radius: 8px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            color: #ffffff;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
            transform: rotateY(-5deg) rotateX(5deg);
            transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .secondary-card:hover .membership-card {
            transform: rotateY(0deg) rotateX(0deg) scale(1.05);
        }

        .card-logo {
            font-size: 16px;
            margin-bottom: 4px;
        }

        .card-text {
            font-size: 10px;
            font-weight: 600;
            letter-spacing: 0.5px;
            font-family: var(--sf-pro-text);
        }

        /* Responsive Design */
        /* Responsive Design for Cinematic Full-Screen Carousel */
        @media (max-width: 1024px) {
            .featured-carousel-section {
                height: 100vh;
            }
            
            .card-content {
                padding: 60px 40px;
                max-width: 800px;
                width: 85%;
            }
            
            .service-featured-title {
                font-size: clamp(3.5rem, 7vw, 6rem);
                margin-bottom: 28px;
            }
            
            .service-featured-price {
                font-size: clamp(2rem, 4vw, 3rem);
                margin-bottom: 32px;
            }
            
            .service-description {
                font-size: clamp(1.3rem, 2.5vw, 2rem);
                margin-bottom: 50px;
            }
            
            .view-details-btn {
                padding: 20px 40px;
                font-size: clamp(1.1rem, 2vw, 1.5rem);
            }
            
            .carousel-nav {
                width: 50px;
                height: 50px;
                font-size: 16px;
            }
            
            .carousel-nav.prev { left: 20px; }
            .carousel-nav.next { right: 20px; }
            
            .carousel-pause-indicator {
                top: 20px;
                right: 20px;
                width: 40px;
                height: 40px;
                font-size: 14px;
            }
        }

        @media (max-width: 768px) {
            .featured-carousel-section {
                height: 100vh;
            }
            
            .card-content {
                padding: 50px 30px;
                max-width: 700px;
                width: 90%;
            }
            
            .service-featured-title {
                font-size: clamp(3rem, 8vw, 5rem);
                margin-bottom: 24px;
                line-height: 1;
            }
            
            .service-featured-price {
                font-size: clamp(1.8rem, 5vw, 2.5rem);
                margin-bottom: 28px;
            }
            
            .service-description {
                font-size: clamp(1.2rem, 3vw, 1.8rem);
                margin-bottom: 40px;
                line-height: 1.3;
            }
            
            .view-details-btn {
                padding: 18px 36px;
                font-size: clamp(1rem, 2.5vw, 1.3rem);
                min-width: 180px;
            }
            
            .carousel-nav {
                width: 44px;
                height: 44px;
                font-size: 14px;
            }
            
            .carousel-nav.prev { left: 16px; }
            .carousel-nav.next { right: 16px; }
            
            .carousel-dots {
                bottom: 20px;
                gap: 8px;
            }
            
            .dot {
                width: 6px;
                height: 6px;
            }
            
            .carousel-pause-indicator {
                top: 16px;
                right: 16px;
                width: 36px;
                height: 36px;
                font-size: 12px;
            }
            
            .carousel-progress {
                height: 3px;
            }
        }

        @media (max-width: 480px) {
            .featured-carousel-section {
                height: 100vh;
            }
            
            .card-content {
                padding: 40px 20px;
                max-width: 600px;
                width: 95%;
            }
            
            .service-featured-title {
                font-size: clamp(2.5rem, 9vw, 4rem);
                margin-bottom: 20px;
                line-height: 0.95;
            }
            
            .service-featured-price {
                font-size: clamp(1.5rem, 6vw, 2rem);
                margin-bottom: 24px;
            }
            
            .service-description {
                font-size: clamp(1.1rem, 4vw, 1.5rem);
                margin-bottom: 32px;
                line-height: 1.3;
            }
            
            .view-details-btn {
                padding: 16px 32px;
                font-size: clamp(0.9rem, 3vw, 1.2rem);
                min-width: 160px;
                gap: 12px;
            }
            
            .view-details-btn i {
                font-size: clamp(0.8rem, 2.5vw, 1rem);
            }
        }

        /* Maintenance Plans Section */
        .maintenance-section {
            padding: 120px 40px;
            background: rgba(255, 255, 255, 0.01);
        }

        .maintenance-content {
            max-width: 1200px;
            margin: 0 auto;
        }

        .plans-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-top: 60px;
        }

        .plan-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 20px;
            padding: 40px 30px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .plan-card:hover {
            background: rgba(255, 255, 255, 0.08);
            transform: translateY(-10px);
            box-shadow: 0 25px 50px rgba(0,0,0,0.2);
        }

        .plan-card.popular {
            border: 2px solid #FFD700;
            background: rgba(255, 215, 0, 0.05);
        }

        .plan-badge {
            position: absolute;
            top: 20px;
            right: -30px;
            background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
            color: #000;
            padding: 5px 40px;
            font-size: 0.8rem;
            font-weight: 600;
            transform: rotate(45deg);
        }

        .plan-header {
            margin-bottom: 30px;
        }

        .plan-name {
            font-size: 1.5rem;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 15px;
        }

        .plan-price {
            display: flex;
            align-items: baseline;
            justify-content: center;
            gap: 5px;
        }

        .currency {
            font-size: 1.2rem;
            color: #FFD700;
            font-weight: 600;
        }

        .amount {
            font-size: 3rem;
            font-weight: 800;
            color: #FFD700;
            line-height: 1;
        }

        .period {
            font-size: 1rem;
            color: rgba(255, 255, 255, 0.7);
        }

        .plan-features {
            margin-bottom: 30px;
        }

        .feature {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
            padding: 8px 0;
        }

        .feature i {
            color: #22c55e;
            font-size: 0.9rem;
        }

        .feature span {
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.95rem;
        }

        .plan-button {
            background: transparent;
            color: #FFD700;
            border: 2px solid #FFD700;
            padding: 12px 30px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-block;
        }

        .plan-button:hover {
            background: #FFD700;
            color: #000;
            transform: translateY(-2px);
        }

        .plan-card.popular .plan-button {
            background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
            color: #000;
            border: none;
        }

        .plan-card.popular .plan-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(255, 215, 0, 0.3);
        }

        /* Professional Split-Screen Section */
        .professional-section {
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: var(--space-5xl) 0;
            background: linear-gradient(135deg, #000000 0%, var(--deep-blue) 25%, #000000 100%);
            position: relative;
            overflow: hidden;
        }

        .professional-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 var(--space-2xl);
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: var(--space-4xl);
            align-items: center;
        }

        .professional-content {
            padding: 100px 60px 60px 60px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            z-index: 2;
        }

        .professional-badge {
            font-size: 0.9rem;
            font-weight: 600;
            color: rgba(255, 255, 255, 0.8);
            letter-spacing: 2px;
            margin-bottom: 20px;
        }

        .professional-title {
            font-size: clamp(var(--text-4xl), 6vw, var(--text-7xl));
            font-weight: 600;
            line-height: var(--line-height-tight);
            margin-bottom: var(--space-xl);
            letter-spacing: var(--letter-spacing-tight);
            font-family: var(--sf-pro-display);
            color: var(--pearl-white);
        }

        .professional-title .location-text {
            color: #ff4444;
            display: block;
        }

        .professional-description {
            font-size: var(--text-lg);
            line-height: var(--line-height-normal);
            color: #a1a1a6;
            margin-bottom: var(--space-2xl);
            font-weight: 400;
            font-family: var(--sf-pro-text);
            letter-spacing: var(--letter-spacing-wide);
        }

        .professional-contact {
            margin-bottom: 40px;
        }

        .professional-contact .contact-item {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
            font-size: 1rem;
            font-weight: 600;
        }

        .professional-contact .contact-item i {
            width: 20px;
            color: #fff;
        }

        .professional-buttons {
            display: flex;
            gap: var(--space-lg);
            margin-bottom: var(--space-2xl);
        }

        .professional-buttons .btn-maintenance,
        .professional-buttons .btn-book {
            background: #ff4444;
            color: #fff;
            padding: 15px 30px;
            font-weight: 700;
            font-size: 0.9rem;
            letter-spacing: 1px;
            text-decoration: none;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }

        .professional-buttons .btn-maintenance:hover,
        .professional-buttons .btn-book:hover {
            background: #e63939;
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(255, 68, 68, 0.3);
        }

        .professional-images {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 60px;
        }

        .professional-images .main-image {
            position: relative;
            z-index: 2;
            width: 100%;
            max-width: 400px;
        }

        .professional-images .detailing-image {
            width: 100%;
            height: 300px;
            object-fit: cover;
            border-radius: 8px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5);
        }

        .professional-images .secondary-image {
            position: absolute;
            top: 20px;
            right: 20px;
            width: 200px;
            z-index: 1;
        }

        .professional-images .car-detail-image {
            width: 100%;
            height: 500px;
            object-fit: cover;
            border-radius: 8px;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.4);
        }

        /* Apple's Premium Services Section */
        .services-section {
            padding: 120px 0;
            max-width: 1400px;
            margin: 0 auto;
            background: radial-gradient(ellipse 80% 50% at 50% -20%, rgba(212, 175, 55, 0.08), transparent),
                        radial-gradient(ellipse 60% 80% at 20% 100%, rgba(0, 122, 255, 0.05), transparent),
                        linear-gradient(180deg, var(--bg-primary) 0%, var(--bg-secondary) 100%);
        }

        .section-header {
            text-align: center;
            margin-bottom: 100px;
            padding: 0 40px;
        }

        .section-title {
            font-size: clamp(2.5rem, 5vw, 3.5rem);
            font-weight: 700;
            color: transparent;
            margin-bottom: 32px;
            letter-spacing: -0.055em;
            font-family: var(--sf-pro-display);
            background: linear-gradient(135deg, 
                        rgba(245, 245, 247, 0.95) 0%,
                        rgba(245, 245, 247, 0.8) 40%,
                        var(--accent-gold) 60%,
                        rgba(212, 175, 55, 0.9) 100%);
            background-clip: text;
            -webkit-background-clip: text;
            text-shadow: 0 0 30px rgba(212, 175, 55, 0.2);
            animation: fadeInScale 1.2s cubic-bezier(0.16, 1, 0.3, 1) both;
        }

        .section-subtitle {
            font-size: clamp(1.1rem, 2vw, 1.375rem);
            color: rgba(245, 245, 247, 0.8);
            max-width: 600px;
            margin: 0 auto;
            line-height: 1.5;
            font-weight: 400;
            letter-spacing: -0.011em;
            font-family: var(--sf-pro-text);
            animation: fadeInScale 1.2s cubic-bezier(0.16, 1, 0.3, 1) 0.2s both;
        }

        /* Apple TV+ Style Full-Screen Services Carousel */
        .featured-carousel-section {
            padding: 0;
            margin: 0;
            position: relative;
            width: 100vw;
            height: 60vh;
            overflow: hidden;
            background: linear-gradient(135deg, #000000 0%, var(--deep-blue) 50%, #000000 100%);
        }

        .carousel-container {
            position: relative;
            width: 100vw;
            height: 60vh;
            overflow: visible;
            border-radius: 0;
            background: transparent;
            backdrop-filter: none;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .carousel-track {
            position: relative;
            width: 160vw;
            height: 60vh;
            display: flex;
            transition: transform 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        }

        .carousel-item {
            flex: 0 0 100vw;
            width: 100vw;
            height: 60vh;
            overflow: hidden;
            position: relative;
            transition: transform 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94), opacity 0.5s ease;
        }
        
        .carousel-item {
            opacity: 0.6;
            transform: scale(0.85);
        }
        
        .carousel-item.active {
            opacity: 1;
            transform: scale(1);
            z-index: 10;
        }
        
        .carousel-item.prev,
        .carousel-item.next {
            opacity: 0.4;
            transform: scale(0.75);
        }

        .featured-service-card {
            background: transparent;
            border-radius: 0;
            padding: 0;
            text-align: center;
            backdrop-filter: none;
            border: none;
            transition: none;
            width: 100vw;
            height: 60vh;
            position: relative;
            overflow: hidden;
        }

        .featured-service-card:hover {
            background: transparent;
            transform: none;
            box-shadow: none;
        }

        .featured-service-card:hover .service-featured-image {
            transform: scale(1.02);
            box-shadow: none;
        }

        .card-image {
            position: absolute;
            top: 0;
            left: 0;
            width: 100vw;
            height: 60vh;
            margin: 0;
            z-index: 1;
        }

        .service-featured-image {
            width: 100vw;
            height: 60vh;
            object-fit: cover;
            border-radius: 0;
            box-shadow: none;
            transition: transform 8s cubic-bezier(0.16, 1, 0.3, 1);
            filter: brightness(0.6) contrast(1.1);
            animation: slowZoom 15s ease-in-out infinite alternate;
        }

        @keyframes slowZoom {
            0% { transform: scale(1); }
            100% { transform: scale(1.05); }
        }

        .service-featured-placeholder {
            width: 100vw;
            height: 60vh;
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            border-radius: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
        }

        .featured-emoji {
            font-size: 12rem;
            opacity: 0.3;
        }

        .card-content {
            position: absolute !important;
            top: 50% !important;
            left: 50% !important;
            transform: translate(-50%, -50%) !important;
            z-index: 1000 !important;
            padding: var(--space-3xl) var(--space-2xl);
            background: rgba(0, 0, 0, 0.75) !important;
            backdrop-filter: blur(30px);
            -webkit-backdrop-filter: blur(30px);
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.08) !important;
            color: #ffffff !important;
            text-align: center;
            max-width: 640px;
            width: 75%;
            min-height: 280px;
            display: flex !important;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            box-shadow: 0 25px 80px rgba(0, 0, 0, 0.6), 0 0 0 1px rgba(255, 255, 255, 0.05);
            visibility: visible !important;
            opacity: 1 !important;
            /* Remove animation delay that might be causing issues */
            animation: none;
        }

        /* Force all text in card content to be visible */
        .card-content * {
            color: #ffffff !important;
            visibility: visible !important;
            opacity: 1 !important;
        }

        /* Apple-style clean card content */
        .carousel-item .card-content {
            background: rgba(0, 0, 0, 0.7) !important;
            border: 1px solid rgba(212, 175, 55, 0.08) !important;
            z-index: 9999 !important;
            position: absolute !important;
            top: 50% !important;
            left: 50% !important;
            transform: translate(-50%, -50%) !important;
            display: flex !important;
            flex-direction: column !important;
            justify-content: center !important;
            align-items: center !important;
            visibility: visible !important;
            opacity: 0.3 !important;
            backdrop-filter: blur(30px) !important;
            -webkit-backdrop-filter: blur(30px) !important;
            transition: all 0.5s cubic-bezier(0.16, 1, 0.3, 1) !important;
            border-radius: var(--radius-2xl) !important;
            box-shadow: var(--shadow-xl), 0 0 0 1px rgba(212, 175, 55, 0.03) !important;
        }
        
        .carousel-item.active .card-content {
            opacity: 1 !important;
        }

        .service-featured-title {
            font-size: clamp(var(--text-3xl), 5vw, var(--text-6xl));
            font-weight: 600;
            color: var(--pearl-white);
            margin-bottom: var(--space-sm);
            letter-spacing: var(--letter-spacing-tight);
            line-height: var(--line-height-tight);
            font-family: var(--sf-pro-display);
            text-align: center;
        }

        .service-featured-price {
            font-size: var(--text-xl);
            color: var(--accent-gold);
            font-weight: 500;
            margin-bottom: var(--space-xs);
            text-align: center;
            font-family: var(--sf-pro-text);
            letter-spacing: var(--letter-spacing-normal);
            line-height: var(--line-height-snug);
        }

        .service-description {
            font-size: var(--text-lg);
            color: #a1a1a6;
            line-height: var(--line-height-normal);
            margin-bottom: var(--space-2xl);
            font-weight: 400;
            font-family: var(--sf-pro-text);
            text-align: center;
            max-width: 480px;
            margin-left: auto;
            margin-right: auto;
            letter-spacing: var(--letter-spacing-wide);
        }

        .view-details-btn i {
            font-size: var(--text-base);
            transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .view-details-btn:hover i {
            transform: translateX(4px);
        }

        /* Cinematic Carousel Navigation */
        .carousel-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(0, 0, 0, 0.4);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: #ffffff;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            z-index: 1000;
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            opacity: 0;
        }

        .featured-carousel-section:hover .carousel-nav {
            opacity: 1;
        }

        .carousel-nav:hover {
            background: rgba(255, 255, 255, 0.15);
            border-color: rgba(255, 255, 255, 0.4);
            transform: translateY(-50%) scale(1.1);
        }

        .carousel-nav.prev { left: 40px; }
        .carousel-nav.next { right: 40px; }

        .carousel-dots {
            position: absolute;
            bottom: 40px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 12px;
            z-index: 1000;
        }

        .dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.4);
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .dot.active {
            background: #ffffff;
            transform: scale(1.2);
        }

        .dot:hover {
            background: rgba(255, 255, 255, 0.8);
            transform: scale(1.1);
        }

        /* Progress Bar */
        .carousel-progress {
            position: absolute;
            bottom: 0;
            left: 0;
            height: 4px;
            background: var(--accent-gold);
            z-index: 1000;
            transition: width 0.1s linear;
        }

        /* Auto-play pause indicator */
        .carousel-pause-indicator {
            position: absolute;
            top: 40px;
            right: 40px;
            background: rgba(0, 0, 0, 0.4);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: #ffffff;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            z-index: 1000;
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            opacity: 0;
        }

        .featured-carousel-section:hover .carousel-pause-indicator {
            opacity: 1;
        }

        .carousel-pause-indicator:hover {
            background: rgba(255, 255, 255, 0.15);
            transform: scale(1.1);
        }

        /* Features Section */
        .features-section {
            padding: 120px 40px;
            background: rgba(255, 255, 255, 0.02);
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 40px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .feature-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 20px;
            padding: 40px 30px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
        }

        .feature-card:hover {
            background: rgba(255, 255, 255, 0.08);
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
        }

        .feature-icon {
            font-size: 3rem;
            color: #FFD700;
            margin-bottom: 20px;
        }

        .feature-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #ffffff;
            margin-bottom: 15px;
        }

        .feature-description {
            color: rgba(255, 255, 255, 0.7);
            line-height: 1.6;
        }

        /* CTA Section */
        .cta-section {
            padding: 120px 40px;
            text-align: center;
            background: radial-gradient(ellipse at center, rgba(255, 215, 0, 0.08) 0%, transparent 70%);
            position: relative;
        }

        .cta-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, transparent 30%, rgba(255, 215, 0, 0.02) 50%, transparent 70%);
        }

        .cta-content {
            max-width: 600px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }

        .cta-badge {
            background: linear-gradient(135deg, #ff6b6b 0%, #ff5722 100%);
            color: #fff;
            padding: 10px 25px;
            border-radius: 25px;
            font-size: 0.9rem;
            font-weight: 700;
            margin-bottom: 25px;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            animation: pulse 2s ease-in-out infinite;
        }

        .cta-features {
            display: flex;
            justify-content: center;
            gap: 40px;
            margin: 30px 0 40px 0;
            flex-wrap: wrap;
        }

        .cta-feature {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.95rem;
            color: rgba(255, 255, 255, 0.8);
            font-weight: 500;
        }

        .cta-feature i {
            color: #FFD700;
            font-size: 1.1rem;
        }

        .cta-urgency {
            margin-top: 25px;
            padding: 15px 25px;
            background: rgba(255, 107, 107, 0.1);
            border: 1px solid rgba(255, 107, 107, 0.3);
            border-radius: 15px;
            font-size: 0.9rem;
            color: #ff6b6b;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .cta-urgency i {
            color: #ff6b6b;
            animation: pulse 1.5s ease-in-out infinite;
        }

        .cta-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 20px;
            letter-spacing: -0.02em;
        }

        .cta-subtitle {
            font-size: 1.1rem;
            color: rgba(255, 255, 255, 0.7);
            margin-bottom: 40px;
            line-height: 1.6;
        }

        /* Footer */
        .footer {
            background: #111;
            padding: 60px 40px 30px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 40px;
            margin-bottom: 40px;
        }

        .footer-section h3 {
            color: #FFD700;
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 20px;
        }

        .footer-section p,
        .footer-section a {
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            line-height: 1.6;
            font-size: 0.9rem;
        }

        .footer-section a:hover {
            color: #FFD700;
        }

        .footer-bottom {
            text-align: center;
            padding-top: 30px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            color: rgba(255, 255, 255, 0.5);
            font-size: 0.85rem;
        }

        /* Hero Section enhancements */
        .hero-badge {
            background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
            color: #000;
            padding: 8px 20px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 20px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        .hero-stats {
            display: flex;
            justify-content: center;
            gap: 40px;
            margin: 30px 0 40px 0;
            flex-wrap: wrap;
        }

        .stat-item {
            text-align: center;
        }

        .stat-number {
            display: block;
            font-size: 1.8rem;
            font-weight: 800;
            color: #FFD700;
            line-height: 1;
        }

        .stat-label {
            display: block;
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.7);
            margin-top: 5px;
        }

        .hero-trust {
            margin-top: 20px;
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.6);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .hero-trust i {
            color: #22c55e;
        }
            .nav-container {
                padding: 0 20px;
            }

            .nav-links {
                gap: 20px;
            }

            .hero-content {
                padding: 0 20px;
            }

            .hero-buttons {
                flex-direction: column;
                align-items: center;
            }

            .hero-stats {
                gap: 20px;
            }

            .problem-grid {
                grid-template-columns: 1fr;
                gap: 40px;
            }

            .plans-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .cta-features {
                flex-direction: column;
                gap: 15px;
            }

            .featured-carousel-section {
                padding: 0 20px;
            }

            .carousel-item {
                flex: 0 0 300px;
            }

            .service-featured-image,
            .service-featured-placeholder {
                width: 250px;
                height: 250px;
            }

            .section-header,
            .features-section,
            .cta-section {
                padding-left: 20px;
                padding-right: 20px;
            }
        }

        /* Smooth scrolling */
        html {
            scroll-behavior: smooth;
        }

        /* Loading animation */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .fade-in-up {
            animation: fadeInUp 0.8s ease forwards;
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .nav-container {
                padding: 0 20px;
                flex-wrap: wrap;
            }

            .nav-menu {
                display: none;
            }

            .hero-content-wrapper {
                padding: 0 20px;
                text-align: center;
                align-items: center;
            }

            .massive-title {
                font-size: clamp(3rem, 15vw, 8rem);
                text-align: center;
            }

            .scroll-indicator {
                bottom: 30px;
                right: 30px;
            }

            .chat-widget {
                bottom: 20px;
                left: 20px;
            }

            .location-badge {
                bottom: 80px;
                right: 20px;
            }

            .nav-links {
                gap: 20px;
            }

            .hero-container {
                grid-template-columns: 1fr;
                min-height: auto;
            }

            .professional-container {
                grid-template-columns: 1fr;
                min-height: auto;
            }

            .professional-content {
                padding: 80px 20px 40px 20px;
                text-align: center;
            }

            .professional-images {
                padding: 20px;
                order: -1;
            }

            .professional-images .secondary-image {
                display: none;
            }

            .professional-buttons {
                flex-direction: column;
                align-items: center;
                gap: 15px;
            }

            .hero-content {
                padding: 80px 20px 40px 20px;
                text-align: center;
            }

            .hero-images {
                padding: 20px;
                order: -1;
            }

            .secondary-image {
                display: none;
            }

            .hero-buttons {
                flex-direction: column;
                align-items: center;
                gap: 15px;
            }

            .problem-grid {
                grid-template-columns: 1fr;
                gap: 40px;
            }

            .plans-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .cta-features {
                flex-direction: column;
                gap: 15px;
            }

            .featured-carousel-section {
                padding: 0 20px;
            }

            .carousel-item {
                flex: 0 0 300px;
            }

            .service-featured-image,
            .service-featured-placeholder {
                width: 250px;
                height: 250px;
            }

            .section-header,
            .features-section,
            .cta-section {
                padding-left: 20px;
                padding-right: 20px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header" id="header">
        <nav class="nav-container">
            <a href="#" class="logo">Ride Revive</a>
            <div class="nav-links">
                <a href="#services" class="nav-link">Services</a>
                <a href="#features" class="nav-link">Features</a>
                <a href="#contact" class="nav-link">Contact</a>
                <a href="auth/login.php" class="cta-button">Sign In</a>
            </div>
        </nav>
    </header>

    <!-- Full-Screen Hero Section -->
    <section class="fullscreen-hero">
        <div class="hero-background">
            <img src="https://images.unsplash.com/photo-1492144534655-ae79c964c9d7?w=1920&h=1080&fit=crop&crop=center" 
                 alt="Luxury car interior" class="hero-bg-image">
            <div class="hero-overlay"></div>
        </div>
        

        <div class="hero-content-wrapper">
            <div class="hero-brand-text">Ride Revive</div>
            <h1 class="massive-title">
                Premium Car Detailing
            </h1>
            <p class="hero-subtitle-premium">Experience automotive perfection with our premium detailing services designed for luxury vehicles.</p>
            <div class="hero-cta-premium">
                <a href="auth/register.php" class="cta-primary-premium">Book Premium Service</a>
                <a href="#services" class="cta-secondary-premium">Explore Services</a>
            </div>
            <div class="scroll-indicator">
                <span class="scroll-text">SCROLL</span>
                <div class="scroll-arrow"></div>
            </div>
        </div>

        
        <!-- Location Badge -->
        <div class="location-badge">
            <div class="badge-content">
                <span class="badge-text">We Are Here</span>
                <div class="badge-dot"></div>
            </div>
        </div>
    </section>

    <!-- Secondary Hero Section -->

    <!-- Apple-Style Split Showcase Section -->
    <section class="apple-showcase-section">
        <div class="showcase-container">
            <div class="showcase-content">
                <div class="showcase-text">
                    <div class="showcase-eyebrow">Mobile Detailing</div>
                    <h2 class="showcase-title">Professional care.<br>Wherever you are.</h2>
                    <p class="showcase-description">Experience premium car detailing without leaving your location. Our certified professionals bring showroom-quality results directly to you.</p>
                    
                    <div class="showcase-features">
                        <div class="showcase-feature">
                            <div class="feature-check">✓</div>
                            <span>Premium products and techniques</span>
                        </div>
                        <div class="showcase-feature">
                            <div class="feature-check">✓</div>
                            <span>Fully mobile service</span>
                        </div>
                        <div class="showcase-feature">
                            <div class="feature-check">✓</div>
                            <span>Certified professionals</span>
                        </div>
                        <div class="showcase-feature">
                            <div class="feature-check">✓</div>
                            <span>Satisfaction guaranteed</span>
                        </div>
                    </div>
                    
                    <a href="auth/register.php" class="showcase-cta">Book Service</a>
                </div>
                
                <div class="showcase-media">
                    <div class="media-container">
                        <img src="https://images.unsplash.com/photo-1607860108855-64acf2078ed9?w=600&h=400&fit=crop&crop=center" 
                             alt="Professional car detailing" class="showcase-image">
                        <div class="media-overlay"></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 4-Grid Services Section -->
    <section class="services-grid-section">
        <div class="services-grid-container">
            <div class="services-grid">
                <div class="service-tile">
                    <div class="tile-image">
                        <img src="https://images.unsplash.com/photo-1544636331-e26879cd4d9b?w=600&h=400&fit=crop&crop=center" 
                             alt="Car Detailing" class="tile-img">
                    </div>
                    <div class="tile-content">
                        <h3 class="tile-title">Car Detailing</h3>
                        <p class="tile-description">Professional car detailing services. Exterior, interior, and full-service packages.</p>
                        <div class="tile-price">Starting at ₱1,500</div>
                        <a href="#" class="tile-cta">Get Quote</a>
                    </div>
                </div>
                
                <div class="service-tile">
                    <div class="tile-image">
                        <img src="https://images.unsplash.com/photo-1558618047-3c8c76ca7d13?w=600&h=400&fit=crop&crop=center" 
                             alt="SUV Detailing" class="tile-img">
                    </div>
                    <div class="tile-content">
                        <h3 class="tile-title">SUV Detailing</h3>
                        <p class="tile-description">Keep your large vehicles in top condition with our professional SUV detailing services.</p>
                        <div class="tile-price">Starting at ₱2,200</div>
                        <a href="#" class="tile-cta">Get Quote</a>
                    </div>
                </div>
                
                <div class="service-tile">
                    <div class="tile-image">
                        <img src="https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?w=600&h=400&fit=crop&crop=center" 
                             alt="Motorcycle Detailing" class="tile-img">
                    </div>
                    <div class="tile-content">
                        <h3 class="tile-title">Motorcycle Detailing</h3>
                        <p class="tile-description">Specialized motorcycle detailing. Professional care for your ride.</p>
                        <div class="tile-price">Starting at ₱800</div>
                        <a href="#" class="tile-cta">Get Quote</a>
                    </div>
                </div>
                
                <div class="service-tile">
                    <div class="tile-image">
                        <img src="https://images.unsplash.com/photo-1607860108855-64acf2078ed9?w=600&h=400&fit=crop&crop=center" 
                             alt="Premium Detailing" class="tile-img">
                    </div>
                    <div class="tile-content">
                        <h3 class="tile-title">Premium Detailing</h3>
                        <p class="tile-description">Our most comprehensive detailing service. Complete transformation.</p>
                        <div class="tile-price">Starting at ₱3,500</div>
                        <a href="#" class="tile-cta">Get Quote</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Professional Split-Screen Section -->
    <section class="professional-section">
        <div class="professional-container">
            <div class="professional-content">
                <div class="professional-badge">WHAT WE DO</div>
                <h2 class="professional-title">
                    PROFESSIONAL MOBILE<br>
                    DETAILING<br>
                    <span class="location-text">IN METRO MANILA</span>
                </h2>
                <p class="professional-description">
                    Welcome to Ride Revive Car Detailing!<br><br>
                    We specialize in all types of vehicles. From cars and 
                    trucks to motorcycles and boats... and anything else that you 
                    can drive. Give us a chance to show you why we are the 
                    most trusted mobile detailing company in the area.
                </p>
                
                <div class="professional-contact">
                    <div class="contact-item">
                        <i class="fas fa-phone"></i>
                        <span>(+63) 123-456-7890</span>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-envelope"></i>
                        <span>INFO@RIDEREVIVE.COM</span>
                    </div>
                </div>

                <div class="professional-buttons">
                    <a href="auth/register.php" class="btn-maintenance">MAINTENANCE PLANS</a>
                    <a href="auth/register.php" class="btn-book">BOOK NOW</a>
                </div>
            </div>
            
            <div class="professional-images">
                <div class="main-image">
                    <img src="https://images.unsplash.com/photo-1607860108855-64acf2078ed9?w=600&h=400&fit=crop&crop=center" 
                         alt="Professional car detailing in action" class="detailing-image">
                </div>
                <div class="secondary-image">
                    <img src="https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=400&h=600&fit=crop&crop=center" 
                         alt="Luxury car paint detail" class="car-detail-image">
                </div>
            </div>
        </div>
    </section>


    <!-- Full-Screen Services Carousel -->
    <section class="featured-carousel-section" id="services">
        <div class="carousel-container">
            <div class="carousel-track" id="featuredTrack" style="width: <?php echo count($services) * 100; ?>vw;">
                <?php foreach ($services as $index => $service): ?>
                <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                    <div class="featured-service-card">
                        <div class="card-image">
                            <?php
                            // Premium car detailing images for all 13 services
                            $imageUrls = [
                                'Ceramic Coating (1-year Protection)' => 'https://images.unsplash.com/photo-1583121274602-3e2820c69888?w=1920&h=1080&fit=crop&crop=center',
                                'Engine Bay Cleaning' => 'https://images.unsplash.com/photo-1486262715619-67b85e0b08d3?w=1920&h=1080&fit=crop&crop=center',
                                'Glass Polishing' => 'https://images.unsplash.com/photo-1449824913935-59a10b8d2000?w=1920&h=1080&fit=crop&crop=center',
                                'Headlight Oxidation Removal' => 'https://images.unsplash.com/photo-1605559424843-9e4c228bf1c2?w=1920&h=1080&fit=crop&crop=center',
                                'Odor Elimination (Ozone Treatment)' => 'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=1920&h=1080&fit=crop&crop=center',
                                'Pet Hair Removal' => 'https://images.unsplash.com/photo-1601362840469-51e4d8d58785?w=1920&h=1080&fit=crop&crop=center',
                                'Upholstery or Leather Treatment' => 'https://images.unsplash.com/photo-1492144534655-ae79c964c9d7?w=1920&h=1080&fit=crop&crop=center',
                                'Watermark and Acid Rain Removal (Full)' => 'https://images.unsplash.com/photo-1607860108855-64acf2078ed9?w=1920&h=1080&fit=crop&crop=center',
                                'Basic Exterior Care' => 'https://images.unsplash.com/photo-1520340356584-f9917d1eea6f?w=1920&h=1080&fit=crop&crop=center',
                                'Express Care + Wax' => 'https://images.unsplash.com/photo-1544636331-e26879cd4d9b?w=1920&h=1080&fit=crop&crop=center',
                                'Full Exterior Detailing' => 'https://images.unsplash.com/photo-1503376780353-7e6692767b70?w=1920&h=1080&fit=crop&crop=center',
                                'Interior Deep Clean' => 'https://images.unsplash.com/photo-1549317661-bd32c8ce0db2?w=1920&h=1080&fit=crop&crop=center',
                                'Platinum Package (Full Interior + Exterior Detail)' => 'https://images.unsplash.com/photo-1494905998402-395d579af36f?w=1920&h=1080&fit=crop&crop=center'
                            ];
                            
                            // Get image URL for this service, fallback to default car image
                            $imageUrl = $imageUrls[$service['service_name']] ?? 'https://images.unsplash.com/photo-1607860108855-64acf2078ed9?w=1920&h=1080&fit=crop&crop=center';
                            echo '<img src="' . $imageUrl . '" alt="' . htmlspecialchars($service['service_name']) . '" class="service-featured-image" loading="lazy">';
                            ?>
                        </div>
                        <div class="card-content">
                            <h3 class="service-featured-title"><?php echo htmlspecialchars($service['service_name']); ?></h3>
                            <p class="service-featured-price">Starting from ₱<?php echo number_format($service['price_small'], 2); ?></p>
                            <p class="service-description"><?php echo htmlspecialchars($service['description']); ?></p>
                            <a href="auth/register.php" class="view-details-btn">
                                <i class="fas fa-arrow-right"></i>
                                Book Now
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Carousel Navigation -->
            <button class="carousel-nav prev" id="carouselPrev">
                <i class="fas fa-chevron-left"></i>
            </button>
            <button class="carousel-nav next" id="carouselNext">
                <i class="fas fa-chevron-right"></i>
            </button>

            <!-- Auto-play pause/play button -->
            <button class="carousel-pause-indicator" id="carouselPause">
                <i class="fas fa-pause"></i>
            </button>

            <!-- Progress Bar -->
            <div class="carousel-progress" id="carouselProgress"></div>
        </div>

        <!-- Carousel Dots -->
        <div class="carousel-dots">
            <?php foreach ($services as $index => $service): ?>
            <span class="dot <?php echo $index === 0 ? 'active' : ''; ?>" onclick="goToSlide(<?php echo $index; ?>)"></span>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Apple-Style Product Showcase Section -->
    <section class="apple-showcase-section">
        <div class="showcase-container">
            <div class="showcase-grid">
                <!-- Premium Detailing Package -->
                <div class="showcase-card">
                    <div class="card-header">
                        <h3 class="product-title">Premium Detail</h3>
                        <p class="product-subtitle">Professional grade care.<br>Complete transformation.</p>
                        <div class="card-actions">
                            <a href="auth/register.php" class="apple-btn-primary">Learn more</a>
                            <a href="auth/register.php" class="apple-btn-secondary">Book</a>
                        </div>
                    </div>
                    <div class="card-visual">
                        <img src="https://images.unsplash.com/photo-1607860108855-64acf2078ed9?w=800&h=600&fit=crop&crop=center" 
                             alt="Premium car detailing" class="showcase-image">
                    </div>
                </div>

                <!-- Ceramic Coating Package -->
                <div class="showcase-card">
                    <div class="card-header">
                        <h3 class="product-title">Ceramic Pro</h3>
                        <p class="product-subtitle">Now enhanced by advanced<br>nano-ceramic technology.</p>
                        <div class="card-actions">
                            <a href="auth/register.php" class="apple-btn-primary">Learn more</a>
                            <a href="auth/register.php" class="apple-btn-secondary">Book</a>
                        </div>
                    </div>
                    <div class="card-visual">
                        <img src="https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=800&h=600&fit=crop&crop=center" 
                             alt="Ceramic coating application" class="showcase-image">
                    </div>
                </div>
            </div>

            <!-- Secondary showcase row -->
            <div class="showcase-grid secondary">
                <!-- Trade In Service -->
                <div class="showcase-card secondary-card">
                    <div class="secondary-content">
                        <div class="secondary-icon">
                            <i class="fas fa-exchange-alt"></i>
                        </div>
                        <h4 class="secondary-title">Trade In</h4>
                        <p class="secondary-text">Get up to ₱5,000–₱15,000<br>in credit when you upgrade<br>your detailing plan.</p>
                        <a href="auth/register.php" class="secondary-btn">Get your estimate</a>
                    </div>
                    <div class="secondary-visual">
                        <img src="https://images.unsplash.com/photo-1492144534655-ae79c964c9d7?w=400&h=300&fit=crop&crop=center" 
                             alt="Car upgrade service" class="secondary-image">
                    </div>
                </div>

                <!-- Membership Card -->
                <div class="showcase-card secondary-card">
                    <div class="secondary-content">
                        <div class="secondary-icon">
                            <i class="fas fa-credit-card"></i>
                        </div>
                        <h4 class="secondary-title">Ride Card</h4>
                        <p class="secondary-text">Get up to 3% Daily Cash back<br>with every service purchase.</p>
                        <div class="secondary-actions">
                            <a href="auth/register.php" class="secondary-btn primary">Learn more</a>
                            <a href="auth/register.php" class="secondary-btn">Apply now</a>
                        </div>
                    </div>
                    <div class="secondary-visual">
                        <div class="card-mockup">
                            <div class="membership-card">
                                <div class="card-logo">🚗</div>
                                <div class="card-text">Ride Revive</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Maintenance Plans Section -->
    <section class="maintenance-section">
        <div class="maintenance-content">
            <div class="section-header">
                <h2 class="section-title">Maintenance Plans</h2>
                <p class="section-subtitle">
                    Keep your car looking its best year-round with our convenient maintenance plans. 
                    Save money and never worry about scheduling again.
                </p>
            </div>

            <div class="plans-grid">
                <div class="plan-card">
                    <div class="plan-header">
                        <h3 class="plan-name">Basic Care</h3>
                        <div class="plan-price">
                            <span class="currency">₱</span>
                            <span class="amount">1,500</span>
                            <span class="period">/month</span>
                        </div>
                    </div>
                    <div class="plan-features">
                        <div class="feature">
                            <i class="fas fa-check"></i>
                            <span>Monthly exterior wash</span>
                        </div>
                        <div class="feature">
                            <i class="fas fa-check"></i>
                            <span>Interior vacuum & wipe</span>
                        </div>
                        <div class="feature">
                            <i class="fas fa-check"></i>
                            <span>Tire cleaning & shine</span>
                        </div>
                        <div class="feature">
                            <i class="fas fa-check"></i>
                            <span>Priority booking</span>
                        </div>
                    </div>
                    <a href="auth/register.php" class="plan-button">Choose Plan</a>
                </div>

                <div class="plan-card popular">
                    <div class="plan-badge">Most Popular</div>
                    <div class="plan-header">
                        <h3 class="plan-name">Premium Care</h3>
                        <div class="plan-price">
                            <span class="currency">₱</span>
                            <span class="amount">2,800</span>
                            <span class="period">/month</span>
                        </div>
                    </div>
                    <div class="plan-features">
                        <div class="feature">
                            <i class="fas fa-check"></i>
                            <span>Bi-weekly full detail</span>
                        </div>
                        <div class="feature">
                            <i class="fas fa-check"></i>
                            <span>Paint protection coating</span>
                        </div>
                        <div class="feature">
                            <i class="fas fa-check"></i>
                            <span>Interior deep cleaning</span>
                        </div>
                        <div class="feature">
                            <i class="fas fa-check"></i>
                            <span>Engine bay cleaning</span>
                        </div>
                        <div class="feature">
                            <i class="fas fa-check"></i>
                            <span>24/7 support</span>
                        </div>
                    </div>
                    <a href="auth/register.php" class="plan-button">Choose Plan</a>
                </div>

                <div class="plan-card">
                    <div class="plan-header">
                        <h3 class="plan-name">Luxury Care</h3>
                        <div class="plan-price">
                            <span class="currency">₱</span>
                            <span class="amount">4,200</span>
                            <span class="period">/month</span>
                        </div>
                    </div>
                    <div class="plan-features">
                        <div class="feature">
                            <i class="fas fa-check"></i>
                            <span>Weekly premium detail</span>
                        </div>
                        <div class="feature">
                            <i class="fas fa-check"></i>
                            <span>Ceramic coating service</span>
                        </div>
                        <div class="feature">
                            <i class="fas fa-check"></i>
                            <span>Leather conditioning</span>
                        </div>
                        <div class="feature">
                            <i class="fas fa-check"></i>
                            <span>Paint correction</span>
                        </div>
                        <div class="feature">
                            <i class="fas fa-check"></i>
                            <span>Concierge service</span>
                        </div>
                    </div>
                    <a href="auth/register.php" class="plan-button">Choose Plan</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features-section" id="features">
        <div class="section-header">
            <h2 class="section-title">Why Choose Ride Revive?</h2>
            <p class="section-subtitle">
                We're committed to providing exceptional service with attention to detail that sets us apart.
            </p>
        </div>

        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-certificate"></i>
                </div>
                <h3 class="feature-title">Professional Service</h3>
                <p class="feature-description">
                    Our certified technicians use premium products and proven techniques to deliver outstanding results every time.
                </p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-mobile-alt"></i>
                </div>
                <h3 class="feature-title">Easy Booking</h3>
                <p class="feature-description">
                    Simple online booking system that allows you to schedule services at your convenience with real-time availability.
                </p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h3 class="feature-title">Quality Guarantee</h3>
                <p class="feature-description">
                    We stand behind our work with a satisfaction guarantee. If you're not happy, we'll make it right.
                </p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <h3 class="feature-title">Flexible Scheduling</h3>
                <p class="feature-description">
                    Book appointments that fit your schedule with our flexible timing options and convenient location services.
                </p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-leaf"></i>
                </div>
                <h3 class="feature-title">Eco-Friendly</h3>
                <p class="feature-description">
                    We use environmentally safe products and water-efficient techniques to protect both your car and the planet.
                </p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <h3 class="feature-title">Transparent Pricing</h3>
                <p class="feature-description">
                    No hidden fees or surprise charges. Our pricing is clear, competitive, and based on your vehicle's specific needs.
                </p>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="cta-content">
            <div class="cta-badge">
                <i class="fas fa-tools"></i>
                HAVE A DIRTY CAR PROBLEM?
            </div>
            <h2 class="cta-title">We're The Solution!</h2>
            <p class="cta-subtitle">
                Don't let a dirty car hurt your image. Our mobile detailing team brings professional 
                results to your location. <strong>Same day service available!</strong>
            </p>
            <div class="cta-features">
                <div class="cta-feature">
                    <i class="fas fa-mobile-alt"></i>
                    <span>We Come To You</span>
                </div>
                <div class="cta-feature">
                    <i class="fas fa-clock"></i>
                    <span>Same Day Service</span>
                </div>
                <div class="cta-feature">
                    <i class="fas fa-shield-check"></i>
                    <span>100% Guaranteed</span>
                </div>
            </div>
            <div class="hero-buttons">
                <a href="auth/register.php" class="btn-primary">
                    <i class="fas fa-calendar-plus"></i>
                    BOOK NOW - Get FREE Quote
                </a>
                <a href="tel:+63-123-456-7890" class="btn-secondary">
                    <i class="fas fa-phone"></i>
                    Call Now: (123) 456-7890
                </a>
            </div>
            <p class="cta-urgency">
                <i class="fas fa-exclamation-circle"></i>
                Limited slots available today! Book now to secure your spot.
            </p>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer" id="contact">
        <div class="footer-content">
            <div class="footer-section">
                <h3>Ride Revive</h3>
                <p>Premium car detailing services that bring out the best in your vehicle. Professional, reliable, and committed to excellence.</p>
            </div>

            <div class="footer-section">
                <h3>Quick Links</h3>
                <p><a href="#services">Our Services</a></p>
                <p><a href="auth/register.php">Book Service</a></p>
                <p><a href="auth/login.php">Customer Login</a></p>
                <p><a href="#features">Why Choose Us</a></p>
            </div>

            <div class="footer-section">
                <h3>Contact Info</h3>
                <p><i class="fas fa-phone"></i> +63 123 456 7890</p>
                <p><i class="fas fa-envelope"></i> info@riderevive.com</p>
                <p><i class="fas fa-map-marker-alt"></i> Metro Manila, Philippines</p>
                <p><i class="fas fa-clock"></i> Mon-Fri: 8AM - 6PM</p>
            </div>

            <div class="footer-section">
                <h3>Follow Us</h3>
                <p><a href="#"><i class="fab fa-facebook"></i> Facebook</a></p>
                <p><a href="#"><i class="fab fa-instagram"></i> Instagram</a></p>
                <p><a href="#"><i class="fab fa-twitter"></i> Twitter</a></p>
                <p><a href="#"><i class="fab fa-youtube"></i> YouTube</a></p>
            </div>
        </div>

        <div class="footer-bottom">
            <p>&copy; 2025 Ride Revive. All rights reserved. | Premium Car Detailing Services</p>
        </div>
    </footer>

    <script>
        // Header scroll effect
        window.addEventListener('scroll', () => {
            const header = document.getElementById('header');
            if (window.scrollY > 100) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        });

        // Apple TV+ Style Cinematic Carousel functionality
        let currentSlide = 0;
        let isAutoPlaying = true;
        let autoPlayInterval;
        let progressInterval;
        let progressWidth = 0;
        const autoPlayDuration = 3500; // 3.5 seconds per slide
        const progressUpdateInterval = 50; // Update progress every 50ms
        
        const slides = document.querySelectorAll('.carousel-item');
        const dots = document.querySelectorAll('.dot');
        const track = document.getElementById('featuredTrack');
        const progressBar = document.getElementById('carouselProgress');
        const pauseBtn = document.getElementById('carouselPause');

        function updateCarousel() {
            // Calculate transform for centering current slide
            const translateX = -currentSlide * 100;
            track.style.transform = `translateX(${translateX}vw)`;
            
            // Update slide states
            slides.forEach((slide, index) => {
                slide.classList.remove('active', 'prev', 'next');
                
                if (index === currentSlide) {
                    slide.classList.add('active');
                } else if (index === currentSlide - 1 || (currentSlide === 0 && index === slides.length - 1)) {
                    slide.classList.add('prev');
                } else if (index === currentSlide + 1 || (currentSlide === slides.length - 1 && index === 0)) {
                    slide.classList.add('next');
                }
            });

            dots.forEach((dot, index) => {
                dot.classList.toggle('active', index === currentSlide);
            });
        }

        function updateProgress() {
            if (isAutoPlaying) {
                progressWidth += (100 / (autoPlayDuration / progressUpdateInterval));
                if (progressWidth >= 100) {
                    progressWidth = 0;
                    nextSlide();
                }
                progressBar.style.width = progressWidth + '%';
            }
        }

        function resetProgress() {
            progressWidth = 0;
            progressBar.style.width = '0%';
        }

        function startAutoPlay() {
            if (autoPlayInterval) clearInterval(autoPlayInterval);
            if (progressInterval) clearInterval(progressInterval);
            
            progressInterval = setInterval(updateProgress, progressUpdateInterval);
        }

        function stopAutoPlay() {
            if (autoPlayInterval) clearInterval(autoPlayInterval);
            if (progressInterval) clearInterval(progressInterval);
        }

        function toggleAutoPlay() {
            isAutoPlaying = !isAutoPlaying;
            const icon = pauseBtn.querySelector('i');
            
            if (isAutoPlaying) {
                icon.className = 'fas fa-pause';
                startAutoPlay();
            } else {
                icon.className = 'fas fa-play';
                stopAutoPlay();
            }
        }

        function nextSlide() {
            currentSlide = (currentSlide + 1) % slides.length;
            updateCarousel();
            resetProgress();
        }

        function prevSlide() {
            currentSlide = (currentSlide - 1 + slides.length) % slides.length;
            updateCarousel();
            resetProgress();
        }

        function goToSlide(index) {
            currentSlide = index;
            updateCarousel();
            resetProgress();
        }

        // Event listeners for carousel
        document.getElementById('carouselNext').addEventListener('click', () => {
            nextSlide();
            if (isAutoPlaying) startAutoPlay();
        });
        
        document.getElementById('carouselPrev').addEventListener('click', () => {
            prevSlide();
            if (isAutoPlaying) startAutoPlay();
        });

        pauseBtn.addEventListener('click', toggleAutoPlay);

        // Pause on hover, resume on leave
        const carouselSection = document.querySelector('.featured-carousel-section');
        carouselSection.addEventListener('mouseenter', () => {
            if (isAutoPlaying) stopAutoPlay();
        });
        
        carouselSection.addEventListener('mouseleave', () => {
            if (isAutoPlaying) startAutoPlay();
        });

        // Keyboard navigation
        document.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowLeft') {
                prevSlide();
                if (isAutoPlaying) startAutoPlay();
            } else if (e.key === 'ArrowRight') {
                nextSlide();
                if (isAutoPlaying) startAutoPlay();
            } else if (e.key === ' ') {
                e.preventDefault();
                toggleAutoPlay();
            }
        });

        // Initialize carousel
        updateCarousel();
        startAutoPlay();

        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Fade in animation on scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('fade-in-up');
                }
            });
        }, observerOptions);

        // Observe elements for animation
        document.querySelectorAll('.feature-card, .section-header').forEach(el => {
            observer.observe(el);
        });

        console.log('Ride Revive landing page loaded successfully!');
    </script>
</body>
</html>