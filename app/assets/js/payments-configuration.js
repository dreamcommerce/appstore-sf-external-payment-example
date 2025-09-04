document.addEventListener('DOMContentLoaded', function() {
    function getUrlParam(name) {
        var results = new RegExp('[\?&]' + name + '=([^&#]*)').exec(window.location.href);
        if (results === null) {
            return null;
        }
        return decodeURI(results[1]) || 0;
    }

    var locale = getUrlParam('translations') || 'pl_PL';
    document.getElementById('create-payment-locale').value = locale;

    document.getElementById('create-payment-btn').addEventListener('click', function() {
        document.getElementById('create-payment-modal').style.display = 'block';
    });

    document.querySelector('.close-create').addEventListener('click', function() {
        document.getElementById('create-payment-modal').style.display = 'none';
    });

    document.querySelector('.cancel-create-btn').addEventListener('click', function() {
        document.getElementById('create-payment-modal').style.display = 'none';
    });

    function safeJson(response) {
        return response.text().then(function(text) {
            try {
                return JSON.parse(text);
            } catch (e) {
                throw new Error('Invalid response from server: ' + text);
            }
        });
    }

    function getSelectedValues(selectElement) {
        var result = [];
        var options = selectElement && selectElement.options;
        var opt;

        for (var i = 0; i < options.length; i++) {
            opt = options[i];
            if (opt.selected) {
                result.push(parseInt(opt.value, 10));
            }
        }
        return result;
    }

    function setSelectedValues(selectElement, values) {
        if (!selectElement || !values) return;

        var stringValues = values.map(function(val) {
            return String(val);
        });

        for (var i = 0; i < selectElement.options.length; i++) {
            selectElement.options[i].selected = stringValues.includes(selectElement.options[i].value);
        }
    }

    document.getElementById('create-payment-form').addEventListener('submit', function(e) {
        e.preventDefault();

        var name = document.getElementById('create-payment-name').value;
        var title = document.getElementById('create-payment-title').value;
        var description = document.getElementById('create-payment-description').value;
        var visible = document.getElementById('create-payment-visible').value === '1';
        var active = document.getElementById('create-payment-active').value === '1';
        var locale = document.getElementById('create-payment-locale').value;
        var currenciesSelect = document.getElementById('create-payment-currencies');
        var currencies = getSelectedValues(currenciesSelect);
        var data = {
            name: name,
            title: title,
            description: description,
            visible: visible,
            active: active,
            locale: locale,
            currencies: currencies
        };


        var urlParams = window.location.search;
        fetch('/app-store/view/payments-configuration/create' + urlParams, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(data)
        })
        .then(function(response) {
            if (response.status === 204 || response.status === 201 || response.status === 200) {
                document.getElementById('create-payment-modal').style.display = 'none';
                window.location.reload();
                alert('Payment has been created.');
            } else {
                response.text().then(function(text) {
                    try {
                        var data = JSON.parse(text);
                        alert('Error while creating payment: ' + (data.error || 'Unknown error'));
                    } catch (e) {
                        alert('Error while creating payment.');
                    }
                });
            }
        })
        .catch(function(error) {
            console.error('Fetch error:', error);
            alert(error.message || 'An error occurred while communicating with the server.');
        });
    });

    document.querySelectorAll('.delete-btn').forEach(function(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            if (!confirm('Are you sure you want to delete this payment?')) {
                return;
            }

            var paymentItem = this.closest('.payment-item');
            var paymentId = parseInt(paymentItem.dataset.id, 10);

            var urlParams = window.location.search;
            fetch('/app-store/view/payments-configuration/delete' + urlParams, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    payment_id: paymentId
                })
            })
            .then(function(response) {
                if (response.status === 204 || response.status === 200) {
                    paymentItem.remove();
                    if (document.querySelectorAll('.payment-item').length === 0) {
                        document.querySelector('.payment-list').innerHTML =
                            '<div class="payment-empty modern">No payments available.</div>';
                    }
                } else {
                    response.text().then(function(text) {
                        try {
                            var data = JSON.parse(text);
                            alert('Error when removing payments: ' + (data.error || 'Unknown error'));
                        } catch (e) {
                            alert('Error when removing payments.');
                        }
                    });
                }
            })
            .catch(function(error) {
                alert(error.message || 'There was a mistake when communicating with the server.');
            });
        });
    });

    document.querySelectorAll('.edit-btn').forEach(function(button) {
        button.addEventListener('click', function() {
            var paymentItem = this.closest('.payment-item');
            var paymentId = paymentItem.dataset.id;
            var paymentName = paymentItem.dataset.name;
            var paymentDescription = paymentItem.dataset.description || '';
            var paymentVisible = paymentItem.dataset.visible === 'visible' ? '1' : '0';
            var paymentActive = paymentItem.dataset.active === 'active' ? '1' : '0';
            var paymentTitle = paymentItem.dataset.title || '';

            document.getElementById('edit-payment-id').value = paymentId;
            document.getElementById('edit-payment-name').value = paymentName;
            document.getElementById('edit-payment-visible').value = paymentVisible;
            document.getElementById('edit-payment-active').value = paymentActive;
            document.getElementById('edit-payment-description').value = paymentDescription;
            document.getElementById('edit-payment-title').value = paymentTitle;

            var paymentCurrencies = [];
            try {
                if (paymentItem.dataset.currencies) {
                    paymentCurrencies = JSON.parse(paymentItem.dataset.currencies);
                }
            } catch (e) {
                console.error('Error parsing currencies:', e);
            }

            var currenciesSelect = document.getElementById('edit-payment-currencies');
            setSelectedValues(currenciesSelect, paymentCurrencies);

            document.getElementById('edit-payment-modal').style.display = 'block';
        });
    });

    document.querySelector('.close').addEventListener('click', function() {
        document.getElementById('edit-payment-modal').style.display = 'none';
    });

    document.querySelector('.cancel-btn').addEventListener('click', function() {
        document.getElementById('edit-payment-modal').style.display = 'none';
    });

    document.getElementById('edit-payment-form').addEventListener('submit', function(e) {
        e.preventDefault();

        var paymentId = parseInt(document.getElementById('edit-payment-id').value, 10);
        var name = document.getElementById('edit-payment-name').value;
        var title = document.getElementById('edit-payment-title').value;
        var description = document.getElementById('edit-payment-description').value;
        var visible = document.getElementById('edit-payment-visible').value === '1';
        var active = document.getElementById('edit-payment-active').value === '1';
        var currenciesSelect = document.getElementById('edit-payment-currencies');
        var currencies = getSelectedValues(currenciesSelect);

        var data = {
            payment_id: paymentId,
            name: name,
            title: title,
            description: description,
            visible: visible,
            active: active,
            currencies: currencies
        };

        var urlParams = window.location.search;
        fetch('/app-store/view/payments-configuration/edit' + urlParams, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(data)
        })
        .then(function(response) {
            if (response.status === 204 || response.status === 200) {
                var paymentItem = document.querySelector('.payment-item[data-id="' + paymentId + '"]');
                var paymentNameSpan = paymentItem.querySelector('.payment-name');
                if (description) {
                    paymentNameSpan.textContent = name + ' (' + description + ')';
                } else {
                    paymentNameSpan.textContent = name;
                }
                paymentItem.dataset.name = name;
                paymentItem.dataset.description = description;
                paymentItem.dataset.title = title;
                paymentItem.dataset.visible = visible ? 'visible' : 'hidden';
                paymentItem.dataset.active = active ? 'active' : 'inactive';
                paymentItem.dataset.currencies = JSON.stringify(currencies);

                var visibilitySpan = paymentItem.querySelector('.payment-visible');
                if (visible) {
                    visibilitySpan.innerHTML = '<span class="status-icon status-yes" title="Visible">&#x2714;</span> Visible';
                } else {
                    visibilitySpan.innerHTML = '<span class="status-icon status-no" title="Hidden">&#x2716;</span> Hidden';
                }

                var activeSpan = paymentItem.querySelector('.payment-active');
                if (active) {
                    activeSpan.innerHTML = '<span class="status-icon status-yes" title="Active">&#x2714;</span> Active';
                } else {
                    activeSpan.innerHTML = '<span class="status-icon status-no" title="Inactive">&#x2716;</span> Inactive';
                }

                document.getElementById('edit-payment-modal').style.display = 'none';
                alert('Payment has been updated.');
            } else {
                response.text().then(function(text) {
                    try {
                        var data = JSON.parse(text);
                        alert('Error while updating payment: ' + (data.error || 'Unknown error'));
                    } catch (e) {
                        alert('Error while updating payment.');
                    }
                });
            }
        })
        .catch(function(error) {
            console.error('Fetch error:', error);
            alert(error.message || 'An error occurred while communicating with the server.');
        });
    });
})
