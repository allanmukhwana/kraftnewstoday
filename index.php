<?php
/**
 * Kraft News Today - Landing Page
 * Main homepage with features, pricing, and call-to-action
 */

require_once 'config.php';

// Redirect to dashboard if logged in
if (isset($_SESSION['user_id'])) {
    redirect('dashboard.php');
}

$page_title = 'AI-Powered News Analysis Platform';
include 'header.php';
?>

<style>
    /* Hero Section */
    .hero-section {
        background: linear-gradient(135deg, var(--primary-color), #083a5f);
        color: white;
        padding: 4rem 0;
        position: relative;
        overflow: hidden;
    }
    
    .hero-section::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="rgba(255,255,255,0.05)" d="M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,122.7C672,117,768,139,864,138.7C960,139,1056,117,1152,106.7C1248,96,1344,96,1392,96L1440,96L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>') no-repeat bottom;
        background-size: cover;
        opacity: 0.3;
    }
    
    .hero-content {
        position: relative;
        z-index: 1;
    }
    
    .hero-title {
        font-size: 3rem;
        font-weight: 800;
        margin-bottom: 1.5rem;
        line-height: 1.2;
    }
    
    .hero-subtitle {
        font-size: 1.25rem;
        margin-bottom: 2rem;
        opacity: 0.9;
    }
    
    .hero-cta {
        display: flex;
        gap: 1rem;
        flex-wrap: wrap;
    }
    
    .hero-cta .btn {
        padding: 1rem 2rem;
        font-size: 1.1rem;
        font-weight: 700;
    }
    
    .hero-image {
        position: relative;
        z-index: 1;
    }
    
    .hero-image img {
        max-width: 100%;
        border-radius: 16px;
        box-shadow: var(--shadow-lg);
    }
    
    /* Features Section */
    .features-section {
        padding: 4rem 0;
        background: white;
    }
    
    .section-title {
        text-align: center;
        margin-bottom: 3rem;
    }
    
    .section-title h2 {
        font-size: 2.5rem;
        font-weight: 800;
        color: var(--primary-color);
        margin-bottom: 1rem;
    }
    
    .section-title p {
        font-size: 1.1rem;
        color: var(--text-light);
    }
    
    .feature-card {
        background: white;
        border-radius: 16px;
        padding: 2rem;
        box-shadow: var(--shadow-md);
        transition: all 0.3s ease;
        height: 100%;
        border: 2px solid transparent;
    }
    
    .feature-card:hover {
        transform: translateY(-8px);
        box-shadow: var(--shadow-lg);
        border-color: var(--primary-color);
    }
    
    .feature-icon {
        width: 64px;
        height: 64px;
        border-radius: 12px;
        background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 1.5rem;
    }
    
    .feature-icon i {
        font-size: 2rem;
        color: white;
    }
    
    .feature-card h3 {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--text-dark);
        margin-bottom: 1rem;
    }
    
    .feature-card p {
        color: var(--text-light);
        line-height: 1.6;
    }
    
    /* How It Works Section */
    .how-it-works-section {
        padding: 4rem 0;
        background: #f8f9fa;
    }
    
    .step-card {
        text-align: center;
        padding: 2rem;
    }
    
    .step-number {
        width: 64px;
        height: 64px;
        border-radius: 50%;
        background: var(--primary-color);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        font-weight: 800;
        margin: 0 auto 1.5rem;
    }
    
    .step-card h3 {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--text-dark);
        margin-bottom: 1rem;
    }
    
    .step-card p {
        color: var(--text-light);
    }
    
    /* Pricing Section */
    .pricing-section {
        padding: 4rem 0;
        background: white;
    }
    
    .pricing-card {
        background: white;
        border-radius: 16px;
        padding: 2.5rem;
        box-shadow: var(--shadow-md);
        transition: all 0.3s ease;
        border: 2px solid var(--border-color);
        height: 100%;
    }
    
    .pricing-card.featured {
        border-color: var(--secondary-color);
        transform: scale(1.05);
        box-shadow: var(--shadow-lg);
    }
    
    .pricing-badge {
        background: var(--secondary-color);
        color: white;
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-size: 0.9rem;
        font-weight: 700;
        display: inline-block;
        margin-bottom: 1rem;
    }
    
    .pricing-card h3 {
        font-size: 1.75rem;
        font-weight: 700;
        color: var(--text-dark);
        margin-bottom: 1rem;
    }
    
    .pricing-price {
        font-size: 3rem;
        font-weight: 800;
        color: var(--primary-color);
        margin-bottom: 0.5rem;
    }
    
    .pricing-price span {
        font-size: 1.25rem;
        color: var(--text-light);
    }
    
    .pricing-features {
        list-style: none;
        padding: 0;
        margin: 2rem 0;
    }
    
    .pricing-features li {
        padding: 0.75rem 0;
        color: var(--text-dark);
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    
    .pricing-features li i {
        color: #28a745;
        font-size: 1.25rem;
    }
    
    .pricing-features li.disabled {
        color: var(--text-light);
        text-decoration: line-through;
    }
    
    .pricing-features li.disabled i {
        color: var(--text-light);
    }
    
    /* CTA Section */
    .cta-section {
        padding: 4rem 0;
        background: linear-gradient(135deg, var(--primary-color), #083a5f);
        color: white;
        text-align: center;
    }
    
    .cta-section h2 {
        font-size: 2.5rem;
        font-weight: 800;
        margin-bottom: 1.5rem;
    }
    
    .cta-section p {
        font-size: 1.25rem;
        margin-bottom: 2rem;
        opacity: 0.9;
    }
    
    @media (max-width: 768px) {
        .hero-title {
            font-size: 2rem;
        }
        
        .hero-subtitle {
            font-size: 1rem;
        }
        
        .hero-cta {
            flex-direction: column;
        }
        
        .hero-cta .btn {
            width: 100%;
        }
        
        .section-title h2 {
            font-size: 2rem;
        }
        
        .pricing-card.featured {
            transform: scale(1);
        }
    }
</style>

<!-- Hero Section -->
<section class="hero-section">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6 hero-content">
                <h1 class="hero-title">
                    AI-Powered News Analysis for Professionals
                </h1>
                <p class="hero-subtitle">
                    Stop drowning in information. Get intelligent, multi-dimensional analysis of industry news delivered to your inbox twice daily.
                </p>
                <div class="hero-cta">
                    <a href="auth_register.php" class="btn btn-secondary">
                        <i class="fas fa-rocket"></i> Start Free Trial
                    </a>
                    <a href="#features" class="btn btn-outline-primary" style="background: white; color: var(--primary-color);">
                        <i class="fas fa-play-circle"></i> See How It Works
                    </a>
                </div>
            </div>
            <div class="col-lg-6 hero-image mt-4 mt-lg-0">
                <div style="background: rgba(255,255,255,0.1); padding: 2rem; border-radius: 16px; backdrop-filter: blur(10px);">
                    <i class="fas fa-chart-line" style="font-size: 10rem; opacity: 0.3;"></i>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="features-section" id="features">
    <div class="container">
        <div class="section-title">
            <h2>Powered by Amazon Bedrock AI</h2>
            <p>Advanced AI agent that thinks like an industry analyst</p>
        </div>
        
        <div class="row g-4">
            <div class="col-lg-4 col-md-6">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-robot"></i>
                    </div>
                    <h3>Autonomous Discovery</h3>
                    <p>AI agent continuously monitors multiple sources, intelligently filtering for relevance to your selected industries.</p>
                </div>
            </div>
            
            <div class="col-lg-4 col-md-6">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-brain"></i>
                    </div>
                    <h3>7-Dimensional Analysis</h3>
                    <p>Comprehensive insights including accuracy, impact, trends, strategy, technical evaluation, competitive intelligence, and bias detection.</p>
                </div>
            </div>
            
            <div class="col-lg-4 col-md-6">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <h3>Automated Delivery</h3>
                    <p>Personalized digests delivered twice daily (7 AM/7 PM) based on your timezone. Never miss important developments.</p>
                </div>
            </div>
            
            <div class="col-lg-4 col-md-6">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-industry"></i>
                    </div>
                    <h3>Multi-Industry Tracking</h3>
                    <p>Monitor multiple industries simultaneously with context-aware analysis tailored to each sector's unique landscape.</p>
                </div>
            </div>
            
            <div class="col-lg-4 col-md-6">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3>Fact Verification</h3>
                    <p>AI agent evaluates source credibility, cross-references claims, and identifies potential misinformation.</p>
                </div>
            </div>
            
            <div class="col-lg-4 col-md-6">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-lightbulb"></i>
                    </div>
                    <h3>Actionable Insights</h3>
                    <p>Strategic recommendations and competitive intelligence to help you make informed decisions faster.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- How It Works Section -->
<section class="how-it-works-section" id="how-it-works">
    <div class="container">
        <div class="section-title">
            <h2>How It Works</h2>
            <p>Get started in minutes</p>
        </div>
        
        <div class="row g-4">
            <div class="col-lg-3 col-md-6">
                <div class="step-card">
                    <div class="step-number">1</div>
                    <h3>Sign Up</h3>
                    <p>Create your free account in seconds</p>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <div class="step-card">
                    <div class="step-number">2</div>
                    <h3>Select Industries</h3>
                    <p>Choose the industries you want to track</p>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <div class="step-card">
                    <div class="step-number">3</div>
                    <h3>AI Analyzes</h3>
                    <p>Our agent discovers and analyzes relevant news</p>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <div class="step-card">
                    <div class="step-number">4</div>
                    <h3>Receive Insights</h3>
                    <p>Get personalized digests in your inbox</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Pricing Section -->
<section class="pricing-section" id="pricing">
    <div class="container">
        <div class="section-title">
            <h2>Simple, Transparent Pricing</h2>
            <p>Start free, upgrade when you need advanced AI analysis</p>
        </div>
        
        <div class="row g-4 justify-content-center">
            <div class="col-lg-5 col-md-6">
                <div class="pricing-card">
                    <h3>Free</h3>
                    <div class="pricing-price">
                        $0<span>/month</span>
                    </div>
                    <p>Perfect for getting started</p>
                    
                    <ul class="pricing-features">
                        <li><i class="fas fa-check-circle"></i> News headlines aggregation</li>
                        <li><i class="fas fa-check-circle"></i> Up to 3 industries</li>
                        <li><i class="fas fa-check-circle"></i> Daily email digests</li>
                        <li><i class="fas fa-check-circle"></i> Basic filtering</li>
                        <li class="disabled"><i class="fas fa-times-circle"></i> AI-powered analysis</li>
                        <li class="disabled"><i class="fas fa-times-circle"></i> Multi-dimensional insights</li>
                        <li class="disabled"><i class="fas fa-times-circle"></i> Trend detection</li>
                    </ul>
                    
                    <a href="auth_register.php" class="btn btn-outline-primary w-100">
                        Get Started Free
                    </a>
                </div>
            </div>
            
            <div class="col-lg-5 col-md-6">
                <div class="pricing-card featured">
                    <span class="pricing-badge">MOST POPULAR</span>
                    <h3>Premium</h3>
                    <div class="pricing-price">
                        $9.99<span>/month</span>
                    </div>
                    <p>For professionals who need insights</p>
                    
                    <ul class="pricing-features">
                        <li><i class="fas fa-check-circle"></i> Everything in Free</li>
                        <li><i class="fas fa-check-circle"></i> Unlimited industries</li>
                        <li><i class="fas fa-check-circle"></i> AI-powered analysis</li>
                        <li><i class="fas fa-check-circle"></i> 7-dimensional insights</li>
                        <li><i class="fas fa-check-circle"></i> Trend detection</li>
                        <li><i class="fas fa-check-circle"></i> Strategic recommendations</li>
                        <li><i class="fas fa-check-circle"></i> Priority support</li>
                    </ul>
                    
                    <a href="auth_register.php" class="btn btn-secondary w-100">
                        Start Premium Trial
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="cta-section">
    <div class="container">
        <h2>Ready to Transform Your News Consumption?</h2>
        <p>Join professionals who stay ahead with AI-powered insights</p>
        <a href="auth_register.php" class="btn btn-secondary btn-lg">
            <i class="fas fa-rocket"></i> Get Started Now - It's Free
        </a>
    </div>
</section>

<?php include 'footer.php'; ?>
