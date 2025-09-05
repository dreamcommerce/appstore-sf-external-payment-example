/**
 * Payment Data Handler
 *
 * The script responsible for detecting the selected payment method and setting
 * the necessary payment data using frontAPI.setPaymentData()
 */
(function() {
    const API_URL = 'https://example.com'; // Replace with your actual API URL
    const JSONP_TIMEOUT = 5000;
    const PAYMENT_CONTAINER_SELECTOR = '.payment-container';
    const API_ENDPOINTS = {
        verify: '/api/shop/payment-methods/verify'
    };

    let currentPaymentMethodId = null;
    let verifiedPaymentMethods = {};
    let apiBaseUrl = null;
    let paymentContainerObserver = null;
    let debounceTimeoutId = null;

    function init() {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', onDOMContentLoaded);
        } else {
            onDOMContentLoaded();
        }
    }

    function onDOMContentLoaded() {
        detectApiBaseUrl();
        if (typeof frontAPI === 'undefined') {
            console.error('[PaymentDataHandler] frontAPI is not available');
            return;
        }

        checkPaymentMethod();
        setupPaymentContainerObserver();
        setupGlobalMutationObserver();
    }

    function detectApiBaseUrl() {
        apiBaseUrl = API_URL;
        console.log('[PaymentDataHandler] API URL set:', apiBaseUrl);
    }

    function buildApiUrl(endpoint) {
        endpoint = endpoint.replace('$', '');
        if (!endpoint.startsWith('/')) {
            endpoint = '/' + endpoint;
        }
        return apiBaseUrl + endpoint;
    }

    function setupPaymentContainerObserver() {
        const paymentContainer = document.querySelector(PAYMENT_CONTAINER_SELECTOR);

        if (paymentContainer) {
            const config = {
                childList: true,
                subtree: true,
                attributes: true,
                attributeFilter: ['class', 'checked', 'selected']
            };

            const callback = function() {
                debounceCheckPaymentMethod();
            };

            paymentContainerObserver = new MutationObserver(callback);
            paymentContainerObserver.observe(paymentContainer, config);

            paymentContainer.addEventListener('click', function() {
                debounceCheckPaymentMethod();
            });
        } else {
            setTimeout(setupPaymentContainerObserver, 1000);
        }
    }

    function setupGlobalMutationObserver() {
        const targetNode = document.body;
        const config = { childList: true, subtree: true };

        const callback = function(mutationsList) {
            for (let mutation of mutationsList) {
                if (mutation.type === 'childList') {
                    const paymentRelated =
                        mutation.target.classList?.contains('payment') ||
                        mutation.target.id?.includes('payment') ||
                        mutation.target.querySelector?.(PAYMENT_CONTAINER_SELECTOR) ||
                        Array.from(mutation.addedNodes).some(node =>
                                node.nodeType === 1 && (
                                    node.classList?.contains('payment') ||
                                    node.id?.includes('payment') ||
                                    node.querySelector?.(PAYMENT_CONTAINER_SELECTOR)
                                )
                        );

                    if (paymentRelated) {
                        const container = document.querySelector(PAYMENT_CONTAINER_SELECTOR);
                        if (container && !paymentContainerObserver) {
                            setupPaymentContainerObserver();
                        }
                        debounceCheckPaymentMethod();
                    }
                }
            }
        };

        const observer = new MutationObserver(callback);
        observer.observe(targetNode, config);
    }

    function debounceCheckPaymentMethod() {
        if (debounceTimeoutId) {
            clearTimeout(debounceTimeoutId);
        }
        debounceTimeoutId = setTimeout(checkPaymentMethod, 200);
    }

    function checkPaymentMethod() {
        if (typeof frontAPI === 'undefined') {
            return;
        }

        try {
            let basket;
            try {
                basket = frontAPI.getBasketInfo({});
            } catch {
                frontAPI.getBasketInfo(function(basketAsync) {
                    if (basketAsync) {
                        processBasketData(basketAsync);
                    }
                }, {});
                return;
            }
            if (basket) {
                processBasketData(basket);
            }
        } catch (error) {
            console.error('[PaymentDataHandler] Error getting basket data', error);
        }
    }

    function processBasketData(basket) {
        if (!basket) return;

        const paymentMethodId = extractPaymentMethodId(basket);

        if (paymentMethodId && paymentMethodId !== currentPaymentMethodId) {
            currentPaymentMethodId = paymentMethodId;
            verifyPaymentMethod(paymentMethodId);
        }
    }

    function extractPaymentMethodId(basket) {
        if (!basket?.payments || !Array.isArray(basket.payments)) {
            return null;
        }
        const selectedPayment = basket.payments.find(payment => payment.selected === true);
        return selectedPayment?.payment_id?.toString() || null;
    }

    function verifyPaymentMethod(paymentMethodId) {
        if (!paymentMethodId) return;

        if (verifiedPaymentMethods.hasOwnProperty(paymentMethodId)) {
            if (verifiedPaymentMethods[paymentMethodId]) {
                setPaymentDataForMethod(paymentMethodId);
            }
            return;
        }

        const shopDomain = window.location.hostname;
        const apiUrl = buildApiUrl(API_ENDPOINTS.verify);

        const requestData = {
            shopUrl: shopDomain,
            paymentMethodId: paymentMethodId
        };

        tryJsonpVerification(apiUrl, requestData, paymentMethodId);
    }

    function tryJsonpVerification(apiUrl, requestData, paymentMethodId) {
        const callbackName = 'paymentCallback_' + Math.random().toString(36).substring(2, 15);

        window[callbackName] = function(data) {
            const isSupported = data.isSupported === true;
            verifiedPaymentMethods[paymentMethodId] = isSupported;

            if (isSupported) {
                setPaymentDataForMethod(paymentMethodId);
            }

            delete window[callbackName];
            try {
                document.body.removeChild(script);
            } catch {}
        };

        const jsonpTimeout = setTimeout(function() {
            if (window[callbackName]) {
                verifiedPaymentMethods[paymentMethodId] = true;
                setPaymentDataForMethod(paymentMethodId);

                delete window[callbackName];
                try {
                    document.body.removeChild(script);
                } catch {}
            }
        }, JSONP_TIMEOUT);

        const queryParams = new URLSearchParams({
            shopUrl: requestData.shopUrl,
            paymentMethodId: requestData.paymentMethodId,
            callback: callbackName
        }).toString();

        const jsonpUrl = apiUrl + '?' + queryParams;

        const script = document.createElement('script');
        script.src = jsonpUrl;
        script.onerror = function() {
            clearTimeout(jsonpTimeout);
            delete window[callbackName];
            try {
                document.body.removeChild(script);
            } catch {}

            verifiedPaymentMethods[paymentMethodId] = true;
            setPaymentDataForMethod(paymentMethodId);
        };

        document.body.appendChild(script);
    }

    function renderPayButton() {
        const scriptElem = document.getElementById('paymentForm');
        if (!scriptElem) {
            return;
        }
        if (document.getElementById('payButton')) {
            return;
        }
        const button = document.createElement('button');
        button.id = 'payButton';
        button.type = 'button';
        button.textContent = 'Opłać zamówienie';
        button.style.marginTop = '16px';
        button.style.padding = '10px 24px';
        button.style.backgroundColor = '#007bff';
        button.style.color = '#fff';
        button.style.border = 'none';
        button.style.borderRadius = '4px';
        button.style.fontSize = '16px';
        button.style.cursor = 'pointer';
        button.style.boxShadow = '0 2px 4px rgba(0,0,0,0.08)';
        button.style.transition = 'background 0.2s';
        button.addEventListener('mouseover', function() {
            button.style.backgroundColor = '#0056b3';
        });
        button.addEventListener('mouseout', function() {
            button.style.backgroundColor = '#007bff';
        });

        button.addEventListener('click', function() {
            // Add on click support here
            alert('Opłacenie zamówienia - funkcja w przygotowaniu');
        });
        scriptElem.parentNode.insertBefore(button, scriptElem.nextSibling);
    }

    function setPaymentDataForMethod(paymentMethodId) {
        if (!paymentMethodId || typeof frontAPI === 'undefined') return;

        const today = new Date();
        const formattedDate = today.toISOString().split('T')[0];

        const paymentData = {
            data: {
                payment_id: parseInt(paymentMethodId, 10),
                payment_data: {
                    pay_date: formattedDate
                }
            }
        };

        try {
            frontAPI.setPaymentData(paymentData);
            renderPayButton(); // Dodaj renderowanie buttona po ustawieniu paymentData
        } catch (error) {
            console.error('[PaymentDataHandler] Error setting payment data', error);
        }
    }

    function cleanup() {
        if (debounceTimeoutId) {
            clearTimeout(debounceTimeoutId);
            debounceTimeoutId = null;
        }

        if (paymentContainerObserver) {
            paymentContainerObserver.disconnect();
            paymentContainerObserver = null;
        }
    }

    init();

    window.paymentDataHandler = {
        checkPaymentMethod,
        processBasketData,
        extractPaymentMethodId,
        verifyPaymentMethod,
        cleanup
    };
})();