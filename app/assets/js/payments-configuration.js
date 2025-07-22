document.addEventListener('DOMContentLoaded', function() {
    function getUrlParam(name) {
        var results = new RegExp('[\?&]' + name + '=([^&#]*)').exec(window.location.href);
        if (results === null) {
            return null;
        }
        return decodeURI(results[1]) || 0;
    }

    var shopCode = getUrlParam('shop');
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

    document.getElementById('create-payment-form').addEventListener('submit', function(e) {
        e.preventDefault();

        var name = document.getElementById('create-payment-name').value;
        var title = document.getElementById('create-payment-title').value;
        var description = document.getElementById('create-payment-description').value;
        var visible = document.getElementById('create-payment-visible').value;
        var locale = document.getElementById('create-payment-locale').value;

        var urlParams = window.location.search;
        fetch('/app-store/view/payments-configuration/create' + urlParams, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: 'name=' + encodeURIComponent(name) +
                '&title=' + encodeURIComponent(title) +
                '&description=' + encodeURIComponent(description) +
                '&visible=' + encodeURIComponent(visible) +
                '&locale=' + encodeURIComponent(locale)
        })
            .then(safeJson)
            .then(function(data) {
                if (data.success) {
                    document.getElementById('create-payment-modal').style.display = 'none';
                    window.location.reload();

                    alert('Payment has been created.');
                } else {
                    alert('Error while creating payment: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(function(error) {
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
            var paymentId = paymentItem.dataset.id;

            var urlParams = window.location.search;
            fetch('/app-store/view/payments-configuration/delete' + urlParams, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: 'payment_id=' + encodeURIComponent(paymentId)
            })
                .then(safeJson)
                .then(function(data) {
                    if (data.success) {
                        paymentItem.remove();
                        if (document.querySelectorAll('.payment-item').length === 0) {
                            document.querySelector('.payment-list').innerHTML =
                                '<div style="color:#888; padding: 24px; text-align:center;">No configured payments.</div>';
                        }
                    } else {
                        alert('Error when removing payments: ' + (data.error || 'Unknown error'));
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
            var paymentVisible = paymentItem.dataset.visible === 'visible' ? '1' : '0';
            var paymentActive = paymentItem.dataset.active === 'active' ? '1' : '0';

            document.getElementById('edit-payment-id').value = paymentId;
            document.getElementById('edit-payment-name').value = paymentName;
            document.getElementById('edit-payment-visible').value = paymentVisible;
            document.getElementById('edit-payment-active').value = paymentActive;
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

        var paymentId = document.getElementById('edit-payment-id').value;
        var name = document.getElementById('edit-payment-name').value;
        var visible = document.getElementById('edit-payment-visible').value;
        var active = document.getElementById('edit-payment-active').value;

        var urlParams = window.location.search;
        fetch('/app-store/view/payments-configuration/edit' + urlParams, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: 'payment_id=' + encodeURIComponent(paymentId) +
                '&name=' + encodeURIComponent(name) +
                '&visible=' + encodeURIComponent(visible) +
                '&active=' + encodeURIComponent(active)
        })
            .then(safeJson)
            .then(function(data) {
                if (data.success) {
                    var paymentItem = document.querySelector('.payment-item[data-id="' + paymentId + '"]');
                    paymentItem.querySelector('.payment-name').textContent = name;
                    paymentItem.querySelector('.payment-visible').textContent = '(visibility: ' + (visible == '1' ? 'visible' : 'hidden') + ')';

                    paymentItem.dataset.name = name;
                    paymentItem.dataset.visible = visible == '1' ? 'visible' : 'hidden';
                    paymentItem.dataset.active = active == '1' ? 'active' : 'inactive';

                    document.getElementById('edit-payment-modal').style.display = 'none';

                    alert('Payment has been updated.');
                } else {
                    alert('Error while updating payment: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(function(error) {
                alert(error.message || 'An error occurred while communicating with the server.');
            });
    });
});
