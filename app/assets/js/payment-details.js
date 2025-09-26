document.addEventListener('DOMContentLoaded', function() {
    var createChannelBtn = document.getElementById('create-channel-btn');
    var createChannelModal = document.getElementById('create-channel-modal');
    var closeCreateChannel = document.querySelector('.close-create-channel');
    var cancelCreateChannel = document.querySelector('.cancel-create-channel');
    var createChannelForm = document.getElementById('create-channel-form');
    var editChannelModal = document.getElementById('edit-channel-modal');
    var closeEditChannel = document.querySelector('.close-edit-channel');
    var cancelEditChannel = document.querySelector('.cancel-edit-channel');
    var editChannelForm = document.getElementById('edit-channel-form');
    var currentChannelId = null;
    var urlParams = window.location.search;

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

    if (closeEditChannel && editChannelModal) {
        closeEditChannel.addEventListener('click', function() {
            editChannelModal.classList.remove('show');
        });
    }
    if (cancelEditChannel && editChannelModal) {
        cancelEditChannel.addEventListener('click', function() {
            editChannelModal.classList.remove('show');
        });
    }
    if (editChannelModal) {
        editChannelModal.addEventListener('click', function(e) {
            if (e.target === editChannelModal) {
                editChannelModal.classList.remove('show');
            }
        });
    }

    var editButtons = document.querySelectorAll('.channel-edit-btn');
    editButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            var channelId = this.getAttribute('data-channel-id');
            if (channelId) {
                loadChannelData(channelId);
            }
        });
    });

    var deleteButtons = document.querySelectorAll('.channel-delete-btn');
    deleteButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            var channelId = this.getAttribute('data-channel-id');
            if (channelId && confirm('Are you sure you want to delete this payment channel?')) {
                deleteChannel(channelId);
            }
        });
    });

    var addTranslationButtons = document.querySelectorAll('.add-translation-btn');
    addTranslationButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            var channelId = this.getAttribute('data-channel-id');
            if (channelId) {
                loadChannelData(channelId, true);
            }
        });
    });

    function loadChannelData(channelId, isNewTranslation = false) {
        currentChannelId = channelId;
        fetch('/app-store/view/payment-details/get-channel/' + channelId + urlParams, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                var channel = data.channel;
                var urlSearchParams = new URLSearchParams(urlParams);
                var currentLocale = urlSearchParams.get('translations') || 'pl_PL';

                document.getElementById('edit-application-channel-id').value = channel.application_channel_id || '';
                document.getElementById('edit-channel-type').value = channel.type || '';

                var existingLocaleInfo = document.querySelector('.locale-info');
                if (existingLocaleInfo) {
                    existingLocaleInfo.remove();
                }

                var modalTitle = document.querySelector('#edit-channel-modal h2');
                if (isNewTranslation) {
                    modalTitle.innerText = 'Dodaj tłumaczenie dla języka: ' + currentLocale;

                    document.getElementById('edit-channel-name').value = '';
                    document.getElementById('edit-channel-description').value = '';
                    document.getElementById('edit-channel-additional-info-label').value = '';

                    var localeInfo = document.createElement('div');
                    localeInfo.className = 'form-group locale-info';
                    localeInfo.innerHTML = '<span class="badge badge-warning locale-badge">' + currentLocale + '</span> ' +
                                          '<span class="locale-info-text">Dodajesz tłumaczenie dla tego języka</span>';

                    var firstFormGroup = document.querySelector('#edit-channel-form .form-group');
                    firstFormGroup.parentNode.insertBefore(localeInfo, firstFormGroup);
                } else {
                    modalTitle.innerText = 'Edit Payment Channel';

                    var translation = {};
                    if (channel.translations && channel.translations[currentLocale]) {
                        translation = channel.translations[currentLocale];
                    }

                    document.getElementById('edit-channel-name').value = translation.name || '';
                    document.getElementById('edit-channel-description').value = translation.description || '';
                    document.getElementById('edit-channel-additional-info-label').value = translation.additional_info_label || '';
                }

                editChannelModal.classList.add('show');
            } else {
                alert('Error: ' + (data.error || 'Unable to load channel data'));
            }
        })
        .catch(() => alert('Error loading channel data.'));
    }

    if (createChannelForm) {
        createChannelForm.addEventListener('submit', function(e) {
            e.preventDefault();
            var applicationChannelIdInput = document.getElementById('application-channel-id');
            var typeInput = document.getElementById('channel-type');
            var nameInput = document.getElementById('channel-name');
            var descriptionInput = document.getElementById('channel-description');
            var additionalInfoLabelInput = document.getElementById('channel-additional-info-label');

            if (!applicationChannelIdInput || !nameInput) {
                console.error('Lack of required fields of the form to create a channel.');
                return;
            }

            var applicationChannelId = applicationChannelIdInput.value;
            var type = typeInput ? typeInput.value : '';
            var name = nameInput.value;
            var description = descriptionInput ? descriptionInput.value : '';
            var additionalInfoLabel = additionalInfoLabelInput ? additionalInfoLabelInput.value : '';

            fetch('/app-store/view/payment-details/create-channel' + urlParams, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    type,
                    application_channel_id: applicationChannelId,
                    name,
                    description,
                    additional_info_label: additionalInfoLabel
                })
            })
            .then(response => {
                if (response.status === 204 || response.status === 201 || response.status === 200) {
                    createChannelModal.classList.remove('show');
                    createChannelForm.reset();
                    window.location.reload();
                } else {
                    response.json().then(data => {
                        alert('Error: ' + (data.error || 'Unknown error'));
                    }).catch(() => alert('Error while creating channel.'));
                }
            })
            .catch(() => alert('Error while creating channel.'));
        });
    }

    if (editChannelForm) {
        editChannelForm.addEventListener('submit', function(e) {
            e.preventDefault();
            if (!currentChannelId) {
                console.error('No channel ID to update.');
                return;
            }

            var applicationChannelIdInput = document.getElementById('edit-application-channel-id');
            var typeInput = document.getElementById('edit-channel-type');
            var nameInput = document.getElementById('edit-channel-name');
            var descriptionInput = document.getElementById('edit-channel-description');
            var additionalInfoLabelInput = document.getElementById('edit-channel-additional-info-label');

            if (!applicationChannelIdInput || !nameInput) {
                console.error('Lack of required fields of the form to update a channel.');
                return;
            }

            var applicationChannelId = applicationChannelIdInput.value;
            var type = typeInput ? typeInput.value : '';
            var name = nameInput.value;
            var description = descriptionInput ? descriptionInput.value : '';
            var additionalInfoLabel = additionalInfoLabelInput ? additionalInfoLabelInput.value : '';

            fetch('/app-store/view/payment-details/update-channel/' + currentChannelId + urlParams, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    type,
                    application_channel_id: applicationChannelId,
                    name,
                    description,
                    additional_info_label: additionalInfoLabel
                })
            })
            .then(response => {
                if (response.status === 204 || response.status === 200) {
                    editChannelModal.classList.remove('show');
                    editChannelForm.reset();
                    window.location.reload();
                } else {
                    response.json().then(data => {
                        alert('Error: ' + (data.error || 'Unknown error'));
                    }).catch(() => alert('Error while updating channel.'));
                }
            })
            .catch(() => alert('Error while updating channel.'));
        });
    }

    function deleteChannel(channelId) {
        fetch('/app-store/view/payment-details/delete-channel/' + channelId + urlParams, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            if (response.status === 204 || response.status === 200) {
                alert('Channel deleted successfully.');
                window.location.reload();
            } else {
                response.json().then(data => {
                    alert('Error: ' + (data.error || 'Unknown error'));
                }).catch(() => alert('Error while deleting channel.'));
            }
        })
        .catch(() => alert('Error while deleting channel.'));
    }
});
