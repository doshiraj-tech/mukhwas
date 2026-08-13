/**
 * Fly-to-Cart Animation
 * Creates a smooth animation where the product image flies from the product card
 * to the cart icon in the navbar along a curved path, shrinking as it goes.
 */

(function () {
    'use strict';

    // ── Inject required CSS ─────────────────────────────────────────────
    const style = document.createElement('style');
    style.textContent = `
        .fly-to-cart-img {
            position: fixed;
            z-index: 99999;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.25), 0 0 0 2px rgba(255,255,255,0.3);
            pointer-events: none;
            transition: none;
            will-change: transform, top, left, width, height, opacity;
        }

        @keyframes cartBounce {
            0%   { transform: scale(1); }
            25%  { transform: scale(1.35); }
            50%  { transform: scale(0.85); }
            75%  { transform: scale(1.15); }
            100% { transform: scale(1); }
        }

        @keyframes cartShake {
            0%, 100% { transform: translateX(0); }
            20%  { transform: translateX(-3px) rotate(-3deg); }
            40%  { transform: translateX(3px) rotate(3deg); }
            60%  { transform: translateX(-2px) rotate(-2deg); }
            80%  { transform: translateX(2px) rotate(2deg); }
        }

        .cart-bounce-anim {
            animation: cartBounce 0.5s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
        }

        .cart-shake-anim {
            animation: cartShake 0.4s ease-in-out;
        }

        @keyframes badgePop {
            0%   { transform: scale(1); }
            50%  { transform: scale(1.8); }
            100% { transform: scale(1); }
        }

        .badge-pop-anim {
            animation: badgePop 0.4s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
        }

        @keyframes flyPulse {
            0%, 100% { box-shadow: 0 8px 32px rgba(0,0,0,0.25), 0 0 0 2px rgba(255,255,255,0.3); }
            50%      { box-shadow: 0 8px 32px rgba(27,77,62,0.5), 0 0 16px 4px rgba(27,77,62,0.3); }
        }

        /* Success ripple on the Add to Cart button */
        @keyframes btnSuccess {
            0%   { box-shadow: 0 0 0 0 rgba(25, 135, 84, 0.5); }
            70%  { box-shadow: 0 0 0 12px rgba(25, 135, 84, 0); }
            100% { box-shadow: 0 0 0 0 rgba(25, 135, 84, 0); }
        }

        .btn-cart-success {
            animation: btnSuccess 0.6s ease-out;
        }
    `;
    document.head.appendChild(style);


    // ── Quadratic Bezier helper ─────────────────────────────────────────
    function quadBezier(t, p0, p1, p2) {
        var u = 1 - t;
        return u * u * p0 + 2 * u * t * p1 + t * t * p2;
    }


    // ── Core animation function ─────────────────────────────────────────
    /**
     * @param {HTMLElement} imgEl  – the product image element to "fly"
     * @param {Function}    onDone – callback after animation completes
     */
    function flyToCart(imgEl, onDone) {
        var cartBtn = document.getElementById('navCartBtn');
        if (!cartBtn || !imgEl) {
            if (onDone) onDone();
            return;
        }

        // Source rect (where the image is on screen)
        var srcRect = imgEl.getBoundingClientRect();
        // Destination rect (the cart button)
        var dstRect = cartBtn.getBoundingClientRect();

        // Create a clone of the image that will fly
        var flyImg = document.createElement('img');
        flyImg.src = imgEl.src;
        flyImg.className = 'fly-to-cart-img';
        flyImg.style.left   = srcRect.left + 'px';
        flyImg.style.top    = srcRect.top + 'px';
        flyImg.style.width  = srcRect.width + 'px';
        flyImg.style.height = srcRect.height + 'px';
        flyImg.style.objectFit = 'cover';
        document.body.appendChild(flyImg);

        // Add a glow pulse while flying
        flyImg.style.animation = 'flyPulse 0.3s ease-in-out infinite';

        // Animation parameters
        var startX = srcRect.left + srcRect.width / 2;
        var startY = srcRect.top + srcRect.height / 2;
        var endX   = dstRect.left + dstRect.width / 2;
        var endY   = dstRect.top + dstRect.height / 2;

        // Control point for the curve (arc upward)
        var cpX = (startX + endX) / 2;
        var cpY = Math.min(startY, endY) - 150;

        var startW = srcRect.width;
        var startH = srcRect.height;
        var endW   = 30;
        var endH   = 30;

        var duration = 650; // ms
        var startTime = null;

        function animate(timestamp) {
            if (!startTime) startTime = timestamp;
            var elapsed = timestamp - startTime;
            var t = Math.min(elapsed / duration, 1);

            // Ease-in-out cubic
            var ease = t < 0.5
                ? 4 * t * t * t
                : 1 - Math.pow(-2 * t + 2, 3) / 2;

            // Position along curve
            var cx = quadBezier(ease, startX, cpX, endX);
            var cy = quadBezier(ease, startY, cpY, endY);

            // Size interpolation
            var w = startW + (endW - startW) * ease;
            var h = startH + (endH - startH) * ease;

            // Opacity: fade out near the end
            var opacity = t < 0.7 ? 1 : 1 - ((t - 0.7) / 0.3) * 0.6;

            // Rotation for extra flair
            var rotation = ease * 360;

            flyImg.style.left    = (cx - w / 2) + 'px';
            flyImg.style.top     = (cy - h / 2) + 'px';
            flyImg.style.width   = w + 'px';
            flyImg.style.height  = h + 'px';
            flyImg.style.opacity = opacity;
            flyImg.style.transform = 'rotate(' + rotation + 'deg)';

            if (t < 1) {
                requestAnimationFrame(animate);
            } else {
                // Remove flying image
                flyImg.remove();

                // Bounce the cart button
                cartBtn.classList.remove('cart-bounce-anim', 'cart-shake-anim');
                // Force reflow
                void cartBtn.offsetWidth;
                cartBtn.classList.add('cart-bounce-anim');

                // Also shake
                setTimeout(function() {
                    cartBtn.classList.add('cart-shake-anim');
                }, 100);

                // Pop the badge
                var badge = cartBtn.querySelector('.cart-badge');
                if (badge) {
                    badge.classList.remove('badge-pop-anim');
                    void badge.offsetWidth;
                    badge.classList.add('badge-pop-anim');
                }

                // Clean up animation classes after they finish
                setTimeout(function() {
                    cartBtn.classList.remove('cart-bounce-anim', 'cart-shake-anim');
                    if (badge) badge.classList.remove('badge-pop-anim');
                }, 700);

                if (onDone) onDone();
            }
        }

        requestAnimationFrame(animate);
    }


    // ── First-Order Congratulations Popup ──────────────────────────────
    var congratsInjected = false;

    function injectCongratsStyles() {
        if (congratsInjected) return;
        congratsInjected = true;

        var congratsStyle = document.createElement('style');
        congratsStyle.textContent = `
            .congrats-overlay {
                position: fixed;
                inset: 0;
                background: rgba(0,0,0,0.6);
                backdrop-filter: blur(6px);
                z-index: 999999;
                display: flex;
                align-items: center;
                justify-content: center;
                opacity: 0;
                animation: congratsFadeIn 0.4s ease forwards;
            }
            @keyframes congratsFadeIn {
                to { opacity: 1; }
            }
            @keyframes congratsFadeOut {
                to { opacity: 0; }
            }
            .congrats-modal {
                background: linear-gradient(145deg, #fff 0%, #f0fdf4 100%);
                border-radius: 24px;
                padding: 40px 36px 32px;
                max-width: 420px;
                width: 90%;
                text-align: center;
                box-shadow: 0 24px 80px rgba(0,0,0,0.25), 0 0 0 1px rgba(255,255,255,0.2);
                transform: scale(0.6) translateY(40px);
                animation: congratsPop 0.5s cubic-bezier(0.34, 1.56, 0.64, 1) 0.1s forwards;
                position: relative;
                overflow: hidden;
            }
            @keyframes congratsPop {
                to { transform: scale(1) translateY(0); }
            }
            .congrats-modal::before {
                content: '';
                position: absolute;
                top: 0; left: 0; right: 0;
                height: 5px;
                background: linear-gradient(90deg, #f59e0b, #10b981, #3b82f6, #ef4444, #f59e0b);
                background-size: 200% 100%;
                animation: congratsShimmer 2s linear infinite;
            }
            @keyframes congratsShimmer {
                to { background-position: -200% 0; }
            }
            .congrats-emoji {
                font-size: 64px;
                display: block;
                margin-bottom: 12px;
                animation: congratsBounce 0.6s ease 0.4s both;
            }
            @keyframes congratsBounce {
                0% { transform: scale(0); }
                50% { transform: scale(1.3); }
                100% { transform: scale(1); }
            }
            .congrats-title {
                font-family: 'Playfair Display', serif;
                font-size: 1.7rem;
                font-weight: 700;
                color: #1a1a2e;
                margin-bottom: 8px;
            }
            .congrats-subtitle {
                font-family: 'Outfit', sans-serif;
                font-size: 1rem;
                color: #64748b;
                margin-bottom: 24px;
                line-height: 1.6;
            }
            .congrats-subtitle strong {
                color: #10b981;
            }
            .congrats-btn {
                display: inline-block;
                background: linear-gradient(135deg, #1b4d3e 0%, #2d7a5f 100%);
                color: #fff;
                border: none;
                padding: 12px 36px;
                border-radius: 50px;
                font-size: 1rem;
                font-weight: 600;
                cursor: pointer;
                font-family: 'Outfit', sans-serif;
                transition: transform 0.2s, box-shadow 0.2s;
                box-shadow: 0 4px 16px rgba(27,77,62,0.3);
            }
            .congrats-btn:hover {
                transform: translateY(-2px);
                box-shadow: 0 6px 24px rgba(27,77,62,0.4);
            }
            /* Confetti particles */
            .confetti-particle {
                position: absolute;
                width: 8px;
                height: 8px;
                border-radius: 2px;
                opacity: 0;
                animation: confettiFall 2s ease-out forwards;
            }
            @keyframes confettiFall {
                0% { opacity: 1; transform: translateY(0) rotate(0deg) scale(1); }
                100% { opacity: 0; transform: translateY(300px) rotate(720deg) scale(0.3); }
            }
        `;
        document.head.appendChild(congratsStyle);
    }

    function showCongratsPopup() {
        injectCongratsStyles();

        var overlay = document.createElement('div');
        overlay.className = 'congrats-overlay';
        overlay.id = 'congratsOverlay';

        // Create confetti particles
        var confettiHTML = '';
        var colors = ['#f59e0b', '#10b981', '#3b82f6', '#ef4444', '#8b5cf6', '#ec4899', '#14b8a6'];
        for (var i = 0; i < 30; i++) {
            var color = colors[i % colors.length];
            var left = Math.random() * 100;
            var delay = Math.random() * 1;
            var size = 6 + Math.random() * 6;
            confettiHTML += '<div class="confetti-particle" style="left:' + left + '%;top:-10px;background:' + color + ';width:' + size + 'px;height:' + size + 'px;animation-delay:' + delay + 's;"></div>';
        }

        overlay.innerHTML = `
            <div class="congrats-modal">
                ${confettiHTML}
                <span class="congrats-emoji">🎉</span>
                <div class="congrats-title">Congratulations!</div>
                <p class="congrats-subtitle">
                    Welcome to <strong>Raj Kathiyawadi Mukhwash</strong> family! 🌿<br>
                    Your first item has been added to the cart.<br>
                    Enjoy the <strong>authentic taste of tradition!</strong>
                </p>
                <button class="congrats-btn" onclick="closeCongratsPopup()">
                    🛒 Continue Shopping
                </button>
            </div>
        `;

        document.body.appendChild(overlay);

        // Close on overlay click (outside modal)
        overlay.addEventListener('click', function(e) {
            if (e.target === overlay) {
                closeCongratsPopup();
            }
        });

        // Auto-close after 6 seconds
        setTimeout(function() {
            closeCongratsPopup();
        }, 6000);
    }

    window.closeCongratsPopup = function() {
        var overlay = document.getElementById('congratsOverlay');
        if (overlay) {
            overlay.style.animation = 'congratsFadeOut 0.3s ease forwards';
            setTimeout(function() {
                if (overlay.parentNode) overlay.parentNode.removeChild(overlay);
            }, 300);
        }
    };


    // ── AJAX Add-to-Cart + Animation ────────────────────────────────────
    /**
     * @param {HTMLElement} imgEl      – the product image to fly
     * @param {number}      productId  – the product ID
     * @param {HTMLElement}  btnEl     – the button (for visual feedback)
     * @param {string}      ajaxUrl    – URL to post to (e.g. 'cart/add.php')
     */
    function addToCartWithFly(imgEl, productId, btnEl, ajaxUrl) {
        if (!imgEl || !productId) return;

        // Disable button during animation
        if (btnEl) {
            btnEl.disabled = true;
            var originalHTML = btnEl.innerHTML;
            btnEl.innerHTML = '<i class="bi bi-check-circle-fill me-2"></i>Adding...';
        }

        // Start the fly animation immediately for responsiveness
        flyToCart(imgEl, function () {
            // After animation, re-enable button
            if (btnEl) {
                btnEl.innerHTML = '<i class="bi bi-check-circle-fill me-2"></i>Added!';
                btnEl.classList.add('btn-cart-success');

                setTimeout(function () {
                    btnEl.innerHTML = originalHTML;
                    btnEl.disabled = false;
                    btnEl.classList.remove('btn-cart-success');
                }, 1200);
            }
        });

        // Fire AJAX request in parallel
        var formData = new FormData();
        formData.append('product_id', productId);

        var xhr = new XMLHttpRequest();
        xhr.open('POST', ajaxUrl, true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.onload = function () {
            if (xhr.status === 200) {
                try {
                    var data = JSON.parse(xhr.responseText);
                    if (data.success) {
                        // Update cart badge count
                        var badge = document.querySelector('#navCartBtn .cart-badge');
                        if (badge) {
                            badge.textContent = data.cart_count;
                        }
                    }
                } catch (e) {
                    console.warn('Fly-to-cart: Could not parse response', e);
                }
            }
        };
        xhr.send(formData);
    }


    // ── Order on Instagram helper with message preview modal & auto-copy ─────────────────
    function orderOnInstagram(productName, price, igHandle, customGreeting) {
        igHandle = (igHandle || window.igDefaultHandle || 'raj_kadhiyawadi_mukhwas').replace('@', '').trim();
        var greeting = customGreeting || window.igDefaultGreeting || "Hello Raj Kathiyawadi Mukhwash! 👋\nI would like to order:";

        var textMsg = greeting;
        if (productName) {
            textMsg += "\n\n📦 Product: " + productName;
            if (price) {
                var cleanPrice = parseFloat(price.toString().replace(/[^0-9.]/g, '')) || 0;
                if (cleanPrice > 0) {
                    textMsg += "\n💰 Price: ₹" + cleanPrice.toFixed(2);
                } else {
                    textMsg += "\n💰 Price: ₹" + price;
                }
            }
            textMsg += "\n🔗 Link: " + window.location.href;
        }
        textMsg += "\n\nPlease share payment and delivery details.";

        // Try Web Share API on supported mobile devices
        if (navigator.share && /Android|iPhone|iPad|iPod/i.test(navigator.userAgent)) {
            navigator.share({
                title: 'Order ' + (productName || 'Mukhwas'),
                text: textMsg
            }).then(function() {
                return;
            }).catch(function() {
                showIgMessageModal(textMsg, igHandle);
            });
            return;
        }

        showIgMessageModal(textMsg, igHandle);
    }

    function showIgMessageModal(textMsg, igHandle) {
        var existingModal = document.getElementById('igOrderModal');
        if (existingModal) existingModal.remove();

        // Copy text to clipboard automatically
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(textMsg).catch(function(e) { console.warn(e); });
        } else {
            var textarea = document.createElement('textarea');
            textarea.value = textMsg;
            textarea.style.position = 'fixed';
            textarea.style.top = '-9999px';
            document.body.appendChild(textarea);
            textarea.select();
            try { document.execCommand('copy'); } catch(e){}
            document.body.removeChild(textarea);
        }

        var modalHtml = '<div id="igOrderModal" style="position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(15,23,42,0.85);backdrop-filter:blur(6px);-webkit-backdrop-filter:blur(6px);z-index:999999;display:flex;align-items:center;justify-content:center;padding:15px;">' +
            '<div style="background:#1e293b;color:#ffffff;border-radius:24px;max-width:480px;width:100%;padding:24px;border:1px solid rgba(255,255,255,0.15);box-shadow:0 25px 60px rgba(0,0,0,0.6);font-family:system-ui,-apple-system,BlinkMacSystemFont,sans-serif;">' +
                '<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;border-bottom:1px solid rgba(255,255,255,0.1);padding-bottom:12px;">' +
                    '<h5 style="margin:0;font-weight:700;font-size:1.15rem;display:flex;align-items:center;gap:10px;">' +
                        '<span style="background:linear-gradient(45deg, #f09433, #e6683c, #dc2743, #cc2366, #bc1888);width:34px;height:34px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:1rem;box-shadow:0 4px 12px rgba(220,39,67,0.4);">📸</span>' +
                        '<span>Instagram Order Message</span>' +
                    '</h5>' +
                    '<button type="button" id="closeIgModalBtn" style="background:rgba(255,255,255,0.1);border:none;color:#94a3b8;width:32px;height:32px;border-radius:50%;font-size:1.4rem;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;line-height:1;">&times;</button>' +
                '</div>' +

                '<div style="background:rgba(34,197,94,0.15);border:1px solid rgba(34,197,94,0.3);color:#4ade80;border-radius:12px;padding:10px 14px;margin-bottom:14px;font-size:0.85rem;font-weight:600;display:flex;align-items:center;gap:8px;">' +
                    '<span style="font-size:1.1rem;">📋</span> Message copied! Paste (Ctrl+V or Long-Press) in Instagram DM.' +
                '</div>' +

                '<div style="margin-bottom:18px;">' +
                    '<label style="font-size:0.82rem;color:#94a3b8;font-weight:600;display:block;margin-bottom:6px;">Your Order Message:</label>' +
                    '<textarea id="igPreviewMsg" readonly style="width:100%;height:120px;background:#0f172a;border:1px solid #334155;color:#e2e8f0;border-radius:12px;padding:12px;font-size:0.86rem;font-family:Consolas,Monaco,monospace;resize:none;outline:none;line-height:1.4;"></textarea>' +
                '</div>' +

                '<div style="display:flex;gap:10px;">' +
                    '<button type="button" id="copyAndGoIgBtn" style="flex:1;background:linear-gradient(45deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%);color:#ffffff;border:none;padding:14px;border-radius:30px;font-weight:700;font-size:0.95rem;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;box-shadow:0 6px 20px rgba(220,39,67,0.45);">' +
                        '<span>Open Instagram DM & Paste 🚀</span>' +
                    '</button>' +
                '</div>' +
            '</div>' +
        '</div>';

        document.body.insertAdjacentHTML('beforeend', modalHtml);

        var modal = document.getElementById('igOrderModal');
        var closeBtn = document.getElementById('closeIgModalBtn');
        var previewTextarea = document.getElementById('igPreviewMsg');
        var goBtn = document.getElementById('copyAndGoIgBtn');

        previewTextarea.value = textMsg;

        closeBtn.onclick = function() {
            modal.remove();
        };

        modal.onclick = function(e) {
            if (e.target === modal) modal.remove();
        };

        goBtn.onclick = function() {
            modal.remove();
            window.open("https://ig.me/m/" + igHandle, "_blank");
        };
    }

    // ── Expose globally ─────────────────────────────────────────────────
    window.flyToCart = flyToCart;
    window.addToCartWithFly = addToCartWithFly;
    window.orderOnInstagram = orderOnInstagram;

})();
