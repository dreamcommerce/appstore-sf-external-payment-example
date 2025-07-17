document.addEventListener('DOMContentLoaded', function() {
    var createChannelBtn = document.getElementById('create-channel-btn');
    var createChannelModal = document.getElementById('create-channel-modal');
    var closeCreateChannel = document.querySelector('.close-create-channel');
    var cancelCreateChannel = document.querySelector('.cancel-create-channel');
    var createChannelForm = document.getElementById('create-channel-form');

    if (createChannelBtn && createChannelModal) {
        createChannelBtn.addEventListener('click', function() {
            createChannelModal.classList.add('show');
        });
    }
    if (closeCreateChannel && createChannelModal) {
        closeCreateChannel.addEventListener('click', function() {
            createChannelModal.classList.remove('show');
        });
    }
    if (cancelCreateChannel && createChannelModal) {
        cancelCreateChannel.addEventListener('click', function() {
            createChannelModal.classList.remove('show');
        });
    }
    if (createChannelModal) {
        createChannelModal.addEventListener('click', function(e) {
            if (e.target === createChannelModal) {
                createChannelModal.classList.remove('show');
            }
        });
    }
    if (createChannelForm) {
        createChannelForm.addEventListener('submit', function(e) {
            e.preventDefault();
            var applicationChannelIdInput = document.getElementById('application-channel-id');
            var typeInput = document.getElementById('channel-type');
            var nameInput = document.getElementById('channel-name');
            var descriptionInput = document.getElementById('channel-description');
            if (!applicationChannelIdInput || !nameInput || !descriptionInput) {
                console.error('Lack of required fields of the form to create a channel.');
                return;
            }
            var applicationChannelId = applicationChannelIdInput.value;
            var type = typeInput.value;
            var name = nameInput.value;
            var description = descriptionInput.value;
            var urlParams = window.location.search;
            fetch('/app-store/view/payment-details/create-channel' + urlParams, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ type, application_channel_id: applicationChannelId, name, description })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    createChannelModal.classList.remove('show');
                    createChannelForm.reset();
                    window.location.reload();
                } else {
                    alert('Error: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(() => alert('Error while creating channel.'));
        });
    }
});
