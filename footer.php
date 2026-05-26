<?php
// footer.php - Original comprehensive footer with interactive pop-ups
?>
<footer class="footer">
    <div class="footer-container">
        <!-- Get to Know Us Section -->
        <div class="footer-section">
            <div class="section-header" onclick="toggleSection('get-to-know')">
                <h4>Get to Know Us</h4>
                <i class="fas fa-chevron-down arrow-icon" id="arrow-get-to-know"></i>
            </div>
            <div class="section-content" id="get-to-know">
                <ul>
                    <li><a href="#" onclick="showPopup('about')">About Us</a></li>
                    <li><a href="#" onclick="showPopup('careers')">Careers</a></li>
                    <li><a href="#" onclick="showPopup('press')">Press Releases</a></li>
                    <li><a href="#" onclick="showPopup('blog')">Our Blog</a></li>
                    <li><a href="#" onclick="showPopup('sustainability')">Sustainability</a></li>
                    <li><a href="#" onclick="showPopup('investors')">Investor Relations</a></li>
                </ul>
            </div>
        </div>

        <!-- Customer Care Section -->
        <div class="footer-section">
            <div class="section-header" onclick="toggleSection('customer-care')">
                <h4>Customer Care</h4>
                <i class="fas fa-chevron-down arrow-icon" id="arrow-customer-care"></i>
            </div>
            <div class="section-content" id="customer-care">
                <ul>
                    <li><a href="#" onclick="showPopup('help')">Help Center</a></li>
                    <li><a href="#" onclick="showPopup('returns')">Returns & Refunds</a></li>
                    <li><a href="#" onclick="showPopup('shipping')">Shipping Information</a></li>
                    <li><a href="#" onclick="showPopup('payment')">Payment Methods</a></li>
                    <li><a href="#" onclick="showPopup('track')">Track Your Order</a></li>
                    <li><a href="#" onclick="showPopup('faqs')">FAQs</a></li>
                    <li><a href="#" onclick="showPopup('contact')">Contact Us</a></li>
                    <li><a href="#" onclick="showPopup('warranty')">Warranty Policy</a></li>
                </ul>
            </div>
        </div>

        <!-- Follow Us Section -->
        <div class="footer-section">
            <div class="section-header" onclick="toggleSection('follow-us')">
                <h4>Follow Us</h4>
                <i class="fas fa-chevron-down arrow-icon" id="arrow-follow-us"></i>
            </div>
            <div class="section-content" id="follow-us">
                <p>Stay connected with us on social media for the latest updates, offers, and tech news.</p>
                <div class="social-links">
                    <a href="#" onclick="showPopup('facebook')"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" onclick="showPopup('twitter')"><i class="fab fa-twitter"></i></a>
                    <a href="#" onclick="showPopup('instagram')"><i class="fab fa-instagram"></i></a>
                    <a href="#" onclick="showPopup('linkedin')"><i class="fab fa-linkedin-in"></i></a>
                    <a href="#" onclick="showPopup('youtube')"><i class="fab fa-youtube"></i></a>
                    <a href="#" onclick="showPopup('tiktok')"><i class="fab fa-tiktok"></i></a>
                </div>
                
                <div class="contact-info">
                    <div>
                        <i class="fas fa-phone"></i>
                        <span onclick="showPopup('phone')">+92 300 1234567</span>
                    </div>
                    <div>
                        <i class="fas fa-envelope"></i>
                        <span onclick="showPopup('email')">support@reloophub.com</span>
                    </div>
                    <div>
                        <i class="fas fa-map-marker-alt"></i>
                        <span onclick="showPopup('address')">123 Electronics Street, Karachi, Pakistan</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Newsletter & App Section -->
        <div class="footer-section">
            <div class="section-header" onclick="toggleSection('newsletter')">
                <h4>Stay Updated</h4>
                <i class="fas fa-chevron-down arrow-icon" id="arrow-newsletter"></i>
            </div>
            <div class="section-content" id="newsletter">
                <p>Subscribe to our newsletter for exclusive offers and updates!</p>
                <div class="newsletter-form">
                    <input type="email" id="newsletter-email" placeholder="Enter your email">
                    <button onclick="subscribeNewsletter()">Subscribe</button>
                </div>
                
                <h4 style="margin-top: 30px; font-size: 16px; cursor: default; border-bottom: none; padding-bottom: 0;">Download Our App</h4>
                <div style="display: flex; gap: 10px; margin-top: 15px;">
                    <a href="#" onclick="showPopup('app-store')" style="background: rgba(255,255,255,0.1); padding: 8px 15px; border-radius: 8px; color: #eae5dc; text-decoration: none; font-size: 12px;">
                        <i class="fab fa-apple"></i> App Store
                    </a>
                    <a href="#" onclick="showPopup('google-play')" style="background: rgba(255,255,255,0.1); padding: 8px 15px; border-radius: 8px; color: #eae5dc; text-decoration: none; font-size: 12px;">
                        <i class="fab fa-google-play"></i> Google Play
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom Footer with Payment Icons -->
    <div class="footer-bottom">
        <div>
            &copy; 2026 Reloop Electronic Hub. All Rights Reserved. | 
            <a href="#" onclick="showPopup('privacy')" style="color: #eae5dc; text-decoration: none;">Privacy Policy</a> | 
            <a href="#" onclick="showPopup('terms')" style="color: #eae5dc; text-decoration: none;">Terms of Service</a> | 
            <a href="#" onclick="showPopup('sitemap')" style="color: #eae5dc; text-decoration: none;">Sitemap</a>
        </div>
        <div class="payment-icons">
            <i class="fab fa-cc-visa" onclick="showPopup('visa')" title="Visa"></i>
            <i class="fab fa-cc-mastercard" onclick="showPopup('mastercard')" title="Mastercard"></i>
            <i class="fab fa-cc-amex" onclick="showPopup('amex')" title="American Express"></i>
            <i class="fab fa-cc-paypal" onclick="showPopup('paypal')" title="PayPal"></i>
            <i class="fas fa-credit-card" onclick="showPopup('jazzcash')" title="JazzCash"></i>
            <i class="fas fa-mobile-alt" onclick="showPopup('easypaisa')" title="EasyPaisa"></i>
        </div>
    </div>
</footer>

<!-- Popup Modal -->
<div id="popupModal" class="popup-modal">
    <div class="popup-content">
        <span class="close-popup" onclick="closePopup()">&times;</span>
        <h3 id="popup-title"></h3>
        <div id="popup-body"></div>
    </div>
</div>

<!-- Footer Styles -->
<style>
.footer {
    background: #020617;
    color: #eae5dc;
    padding: 60px 50px 20px;
    margin-top: 40px;
    border-top: 1px solid #b8af06;
}

.footer-container {
    max-width: 1400px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 40px;
}

.footer-section {
    width: 100%;
}

/* Section Header with Arrow */
.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    cursor: pointer;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 2px solid rgba(216, 238, 104, 0.3);
    width: 100%;
}

.section-header h4 {
    color: #d8ee68;
    font-size: 18px;
    margin: 0;
    flex: 1;
    text-align: left;
}

.section-header .arrow-icon {
    color: #d8ee68;
    font-size: 16px;
    transition: transform 0.3s ease;
    margin-left: 15px;
}

/* Arrow rotation when section is open */
.section-header.open .arrow-icon {
    transform: rotate(180deg);
}

/* Section Content - Collapsible */
.footer-section .section-content {
    max-height: 1000px;
    overflow: hidden;
    transition: max-height 0.5s ease;
    margin-bottom: 20px;
}

.footer-section .section-content.collapsed {
    max-height: 0;
    margin-bottom: 0;
}

.footer-section ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.footer-section ul li {
    margin-bottom: 12px;
}

.footer-section ul li a {
    color: #eae5dc;
    text-decoration: none;
    transition: color 0.3s, padding-left 0.3s;
    font-size: 14px;
    cursor: pointer;
    display: inline-block;
}

.footer-section ul li a:hover {
    color: #d8ee68;
    padding-left: 5px;
}

.footer-section p {
    font-size: 14px;
    line-height: 1.8;
    margin-bottom: 15px;
    color: #eae5dc;
}

.social-links {
    display: flex;
    gap: 15px;
    margin-top: 15px;
    flex-wrap: wrap;
}

.social-links a {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    background: rgba(255,255,255,0.1);
    border-radius: 50%;
    color: #eae5dc;
    text-decoration: none;
    transition: transform 0.3s, background 0.3s;
    cursor: pointer;
}

.social-links a:hover {
    transform: translateY(-3px);
    background: #d8ee68;
    color: #020617;
}

.contact-info {
    margin-top: 15px;
}

.contact-info div {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 10px;
    font-size: 14px;
}

.contact-info div span {
    cursor: pointer;
    transition: color 0.3s;
}

.contact-info div span:hover {
    color: #d8ee68;
}

.contact-info i {
    width: 20px;
    color: #d8ee68;
}

.newsletter-form {
    margin-top: 20px;
}

.newsletter-form input {
    width: 100%;
    padding: 12px;
    border: none;
    border-radius: 8px;
    background: rgba(255,255,255,0.1);
    color: #eae5dc;
    margin-bottom: 10px;
}

.newsletter-form input::placeholder {
    color: rgba(255,255,255,0.5);
}

.newsletter-form button {
    width: 100%;
    padding: 12px;
    border: none;
    border-radius: 8px;
    background: linear-gradient(135deg, #d8ee68, #375113);
    color: #020617;
    font-weight: 600;
    cursor: pointer;
    transition: transform 0.3s;
}

.newsletter-form button:hover {
    transform: translateY(-2px);
}

.footer-bottom {
    max-width: 1400px;
    margin: 40px auto 0;
    padding-top: 20px;
    border-top: 1px solid rgba(255,255,255,0.1);
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
    font-size: 13px;
    color: rgba(255,255,255,0.7);
}

.payment-icons {
    display: flex;
    gap: 15px;
    font-size: 24px;
    flex-wrap: wrap;
}

.payment-icons i {
    color: rgba(255,255,255,0.7);
    transition: color 0.3s;
    cursor: pointer;
}

.payment-icons i:hover {
    color: #d8ee68;
}

/* Popup Modal Styles */
.popup-modal {
    display: none;
    position: fixed;
    z-index: 2000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.8);
    animation: fadeIn 0.3s;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.popup-content {
    background: linear-gradient(145deg, #ebf974, #b8c079);
    margin: 10% auto;
    padding: 30px;
    border-radius: 20px;
    width: 90%;
    max-width: 500px;
    max-height: 80vh;
    overflow-y: auto;
    box-shadow: 0 25px 50px rgba(0,0,0,0.9);
    animation: slideDown 0.3s;
    position: relative;
}

@keyframes slideDown {
    from { transform: translateY(-50px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}

.close-popup {
    position: absolute;
    right: 20px;
    top: 15px;
    font-size: 28px;
    font-weight: bold;
    color: #0a1f44;
    cursor: pointer;
    transition: color 0.3s;
}

.close-popup:hover {
    color: #dc3545;
}

.popup-content h3 {
    color: #0a1f44;
    font-size: 24px;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 2px solid #1c1917;
}

.popup-content p {
    color: #1c1917;
    font-size: 16px;
    line-height: 1.8;
    margin-bottom: 15px;
}

.popup-content ul {
    color: #1c1917;
    margin-left: 20px;
    margin-bottom: 15px;
}

.popup-content li {
    margin-bottom: 8px;
}

.popup-content strong {
    color: #0a1f44;
}

.popup-content .info-box {
    background: rgba(10,31,68,0.1);
    padding: 15px;
    border-radius: 10px;
    margin-top: 15px;
}

@media (max-width: 768px) {
    .footer {
        padding: 40px 20px 20px;
    }
    .footer-bottom {
        flex-direction: column;
        text-align: center;
    }
    .payment-icons {
        justify-content: center;
    }
    .popup-content {
        margin: 20% auto;
        padding: 20px;
    }
}
</style>

<!-- Footer JavaScript -->
<script>
// Global variable to track section states
let sectionStates = {
    'get-to-know': true,
    'customer-care': true,
    'follow-us': true,
    'newsletter': true
};

// Toggle footer sections
function toggleSection(sectionId) {
    if (event) {
        event.preventDefault();
    }
    
    const section = document.getElementById(sectionId);
    const header = section.parentElement.querySelector('.section-header');
    const arrow = document.getElementById('arrow-' + sectionId);
    
    section.classList.toggle('collapsed');
    header.classList.toggle('open');
    sectionStates[sectionId] = !section.classList.contains('collapsed');
}

// Show popup function
function showPopup(key) {
    if (event) {
        event.preventDefault();
    }
    
    const modal = document.getElementById('popupModal');
    const title = document.getElementById('popup-title');
    const body = document.getElementById('popup-body');
    
    if (popupContent[key]) {
        title.innerHTML = popupContent[key].title;
        body.innerHTML = popupContent[key].content;
    } else {
        title.innerHTML = 'Information';
        body.innerHTML = '<p>More details coming soon!</p>';
    }
    
    modal.style.display = 'block';
    document.body.style.overflow = 'hidden';
}

// Close popup function
function closePopup() {
    document.getElementById('popupModal').style.display = 'none';
    document.body.style.overflow = 'auto';
}

// Newsletter subscription
function subscribeNewsletter() {
    const email = document.getElementById('newsletter-email').value;
    if (email && email.includes('@')) {
        alert('Thank you for subscribing to our newsletter!');
        document.getElementById('newsletter-email').value = '';
    } else {
        alert('Please enter a valid email address');
    }
}

// Close popup when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('popupModal');
    if (event.target == modal) {
        closePopup();
    }
}

// Popup content data 
const popupContent = {
    about: {
        title: 'About Reloop Electronic Hub',
        content: '<p>Reloop Electronic Hub is Pakistan\'s premier destination for cutting-edge electronics and gadgets. Founded in 2020, we\'ve been dedicated to providing the latest technology products at competitive prices.</p><p>Our mission is to make high-quality electronics accessible to everyone while providing exceptional customer service and support.</p><div class="info-box"><p><strong>Founded:</strong> 2020</p><p><strong>Headquarters:</strong> Karachi, Pakistan</p><p><strong>Employees:</strong> 50+</p><p><strong>Products:</strong> 1000+</p></div>'
    },
    careers: {
        title: 'Careers at Reloop',
        content: '<p>Join our dynamic team and help shape the future of electronics retail in Pakistan!</p><p><strong>Current Openings:</strong></p><ul><li>Senior Web Developer</li><li>Customer Support Representative</li><li>Digital Marketing Specialist</li><li>Warehouse Manager</li><li>Product Photographer</li></ul><p>Send your CV to: careers@reloophub.com</p>'
    },
    press: {
        title: 'Press Releases',
        content: '<p><strong>Latest News:</strong></p><ul><li><strong>March 2026:</strong> Reloop Hub expands to Lahore with new flagship store</li><li><strong>January 2026:</strong> Launched exclusive partnership with Apple Pakistan</li><li><strong>November 2025:</strong> Won "Best E-commerce Platform" award</li><li><strong>August 2025:</strong> Reached 100,000 happy customers milestone</li></ul><p>For press inquiries: press@reloophub.com</p>'
    },
    blog: {
        title: 'Our Blog',
        content: '<p><strong>Latest Articles:</strong></p><ul><li>"Top 10 Smartphones of 2026"</li><li>"How to Choose the Perfect Laptop"</li><li>"Smart Home Setup Guide"</li><li>"Gaming PC vs Console: Which is Better?"</li><li>"Future of Wearable Technology"</li></ul><p>Visit our blog for more tech insights and reviews!</p>'
    },
    sustainability: {
        title: 'Sustainability at Reloop',
        content: '<p>We\'re committed to a greener future:</p><ul><li><strong>E-waste Recycling:</strong> Free recycling program for old electronics</li><li><strong>Eco-friendly Packaging:</strong> 100% biodegradable packaging materials</li><li><strong>Energy Efficiency:</strong> Promoting energy-efficient products</li><li><strong>Carbon Neutral:</strong> Offset 50% of our carbon footprint</li></ul><p>Join us in making technology sustainable!</p>'
    },
    investors: {
        title: 'Investor Relations',
        content: '<p><strong>Company Performance:</strong></p><ul><li>Annual Revenue: PKR 500M+</li><li>Growth Rate: 40% YoY</li><li>Market Share: 15% in electronics sector</li></ul><p><strong>Contact:</strong> investors@reloophub.com</p>'
    },
    help: {
        title: 'Help Center',
        content: '<p>Welcome to our Help Center! Find answers to common questions about orders, payments, shipping, and returns.</p>'
    },
    returns: {
        title: 'Returns & Refunds',
        content: '<p><strong>Easy 14-Day Return Policy</strong></p><ul><li>Items must be unused and in original packaging</li><li>Free returns within 14 days of delivery</li><li>Refund processed within 5-7 business days</li></ul>'
    },
    shipping: {
        title: 'Shipping Information',
        content: '<p><strong>Delivery Options:</strong></p><ul><li>Standard Shipping: 3-5 business days (Free over PKR 10,000)</li><li>Express Shipping: 1-2 business days (PKR 500)</li><li>Same Day Delivery: Available in Karachi (PKR 800)</li></ul>'
    },
    payment: {
        title: 'Payment Methods',
        content: '<p>We accept Credit/Debit Cards, JazzCash, EasyPaisa, Bank Transfer, and Cash on Delivery.</p>'
    },
    track: {
        title: 'Track Your Order',
        content: '<p>Log in to your account and go to "Order History" to track your order status in real-time.</p>'
    },
    faqs: {
        title: 'FAQs',
        content: '<p><strong>Q: How do I create an account?</strong><br>A: Click "Login/Signup" and fill in your details.</p><p><strong>Q: Is my payment information secure?</strong><br>A: Yes, we use 256-bit SSL encryption.</p>'
    },
    contact: {
        title: 'Contact Us',
        content: '<p><strong>Phone:</strong> +92 300 1234567 (9AM-6PM, Mon-Sat)</p><p><strong>Email:</strong> support@reloophub.com</p><p><strong>Office:</strong> 123 Electronics Street, Karachi, Pakistan</p>'
    },
    warranty: {
        title: 'Warranty Policy',
        content: '<p>All products come with manufacturer warranty. Warranty periods vary from 1-3 years depending on the product.</p>'
    },
    facebook: {
        title: 'Follow us on Facebook',
        content: '<p>Join our Facebook community for daily tech news, exclusive discounts, and live product launches!</p><p><strong>Facebook:</strong> @reloopelectronichub</p>'
    },
    twitter: {
        title: 'Follow us on Twitter',
        content: '<p>Stay updated with real-time order updates, flash sale announcements, and tech industry news.</p><p><strong>Twitter:</strong> @reloophub</p>'
    },
    instagram: {
        title: 'Follow us on Instagram',
        content: '<p>Visual inspiration for tech lovers! Product unboxing, customer photos, and giveaways.</p><p><strong>Instagram:</strong> @reloop.hub</p>'
    },
    linkedin: {
        title: 'Connect on LinkedIn',
        content: '<p>Professional network for company updates, career opportunities, and industry insights.</p><p><strong>LinkedIn:</strong> /company/reloop-hub</p>'
    },
    youtube: {
        title: 'Subscribe on YouTube',
        content: '<p>Watch product reviews, comparisons, setup tutorials, and unboxing videos.</p><p><strong>YouTube:</strong> @reloophub</p>'
    },
    tiktok: {
        title: 'Follow on TikTok',
        content: '<p>Fun tech content, 15-second product highlights, and trendy tech challenges!</p><p><strong>TikTok:</strong> @reloop.hub</p>'
    },
    phone: {
        title: 'Call Us',
        content: '<p><strong>Customer Support:</strong> +92 300 1234567</p><p>Monday to Saturday, 9AM - 6PM</p>'
    },
    email: {
        title: 'Email Us',
        content: '<p><strong>General Support:</strong> support@reloophub.com</p><p><strong>Sales:</strong> sales@reloophub.com</p><p><strong>Returns:</strong> returns@reloophub.com</p>'
    },
    address: {
        title: 'Our Location',
        content: '<p><strong>Head Office:</strong></p><p>123 Electronics Street, Block 6, PECHS, Karachi, Pakistan - 75400</p><p><strong>Hours:</strong> 10AM - 10PM (Daily)</p>'
    },
    'app-store': {
        title: 'Download on App Store',
        content: '<p>Get our iPhone app for easy browsing, push notifications, and exclusive app-only discounts!</p>'
    },
    'google-play': {
        title: 'Get it on Google Play',
        content: '<p>Download our Android app for seamless shopping, secure payments, and real-time order updates.</p>'
    },
    privacy: {
        title: 'Privacy Policy',
        content: '<p>Your privacy matters. We never sell your data. Read our full privacy policy for complete details.</p>'
    },
    terms: {
        title: 'Terms of Service',
        content: '<p>By using our site, you agree to our terms. You must be 18+ to create an account.</p>'
    },
    sitemap: {
        title: 'Sitemap',
        content: '<p><strong>Quick links:</strong> Home, Products, Cart, Checkout, Login, Register, Dashboard</p>'
    },
    visa: {
        title: 'Visa',
        content: '<p>We accept all Visa credit, debit, and prepaid cards with 3D Secure authentication.</p>'
    },
    mastercard: {
        title: 'Mastercard',
        content: '<p>We accept Mastercard credit, debit, and prepaid cards with Mastercard SecureCode.</p>'
    },
    amex: {
        title: 'American Express',
        content: '<p>We proudly accept American Express with member rewards points eligible.</p>'
    },
    paypal: {
        title: 'PayPal',
        content: '<p>Pay quickly and securely with PayPal. Buyer protection included.</p>'
    },
    jazzcash: {
        title: 'JazzCash',
        content: '<p>Pay with JazzCash mobile account. No additional charges.</p>'
    },
    easypaisa: {
        title: 'EasyPaisa',
        content: '<p>Pay with EasyPaisa mobile account or at any EasyPaisa shop nationwide.</p>'
    }
};

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.section-content').forEach(section => {
        section.classList.remove('collapsed');
    });
    document.querySelectorAll('.section-header').forEach(header => {
        header.classList.remove('open');
    });
});
</script>