// Document Ready Function
$(document).ready(function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Form Validation
    $('form').on('submit', function(e) {
        if (!this.checkValidity()) {
            e.preventDefault();
            e.stopPropagation();
        }
        $(this).addClass('was-validated');
    });

    // Image Preview for File Uploads
    $('.custom-file-input').on('change', function() {
        let fileName = $(this).val().split('\\').pop();
        $(this).next('.custom-file-label').addClass("selected").html(fileName);
        
        if (this.files && this.files[0]) {
            let reader = new FileReader();
            reader.onload = function(e) {
                $('#imagePreview').attr('src', e.target.result);
            }
            reader.readAsDataURL(this.files[0]);
        }
    });

    // Chat Functionality
    if ($('#chatForm').length) {
        $('#chatForm').on('submit', function(e) {
            e.preventDefault();
            let message = $('#messageInput').val().trim();
            if (message) {
                sendMessage(message);
                $('#messageInput').val('');
            }
        });

        // Auto-scroll chat to bottom
        let chatContainer = $('.chat-container');
        chatContainer.scrollTop(chatContainer[0].scrollHeight);
    }

    // Product Search
    if ($('#searchForm').length) {
        $('#searchForm').on('submit', function(e) {
            e.preventDefault();
            let searchQuery = $('#searchInput').val().trim();
            if (searchQuery) {
                window.location.href = `?page=products&search=${encodeURIComponent(searchQuery)}`;
            }
        });
    }

    // Payment Form Handling
    if ($('#paymentForm').length) {
        $('#paymentForm').on('submit', function(e) {
            e.preventDefault();
            let paymentMethod = $('input[name="payment_method"]:checked').val();
            if (paymentMethod) {
                processPayment(paymentMethod);
            }
        });
    }
});

// Chat Functions
function sendMessage(message) {
    $.ajax({
        url: 'ajax/send_message.php',
        method: 'POST',
        data: {
            message: message,
            receiver_id: $('#receiverId').val(),
            csrf_token: $('#csrfToken').val()
        },
        success: function(response) {
            if (response.success) {
                appendMessage(message, true);
                updateChat();
            }
        }
    });
}

function updateChat() {
    $.ajax({
        url: 'ajax/get_messages.php',
        method: 'GET',
        data: {
            receiver_id: $('#receiverId').val()
        },
        success: function(response) {
            if (response.success) {
                displayMessages(response.messages);
            }
        }
    });
}

function appendMessage(message, isSent) {
    let messageClass = isSent ? 'sent' : 'received';
    let messageHtml = `
        <div class="chat-message ${messageClass}">
            ${message}
        </div>
    `;
    $('.chat-container').append(messageHtml);
    $('.chat-container').scrollTop($('.chat-container')[0].scrollHeight);
}

// Payment Processing
function processPayment(method) {
    $('#paymentForm').hide();
    $('.spinner').show();
    
    $.ajax({
        url: 'ajax/process_payment.php',
        method: 'POST',
        data: {
            payment_method: method,
            amount: $('#amount').val(),
            phone: $('#phone').val(),
            csrf_token: $('#csrfToken').val()
        },
        success: function(response) {
            $('.spinner').hide();
            if (response.success) {
                $('#paymentForm').html(`
                    <div class="alert alert-success">
                        Payment initiated successfully! Please complete the payment on your phone.
                    </div>
                `);
                checkPaymentStatus(response.transaction_id);
            } else {
                $('#paymentForm').html(`
                    <div class="alert alert-danger">
                        ${response.message}
                    </div>
                `).show();
            }
        }
    });
}

function checkPaymentStatus(transactionId) {
    let checkInterval = setInterval(function() {
        $.ajax({
            url: 'ajax/check_payment.php',
            method: 'POST',
            data: {
                transaction_id: transactionId,
                csrf_token: $('#csrfToken').val()
            },
            success: function(response) {
                if (response.status === 'completed') {
                    clearInterval(checkInterval);
                    window.location.href = '?page=payment_success';
                } else if (response.status === 'failed') {
                    clearInterval(checkInterval);
                    $('#paymentForm').html(`
                        <div class="alert alert-danger">
                            Payment failed. Please try again.
                        </div>
                    `).show();
                }
            }
        });
    }, 5000);
}

// Utility Functions
function formatPrice(price) {
    return 'TSh ' + parseFloat(price).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
}

function showLoading() {
    $('.spinner').show();
}

function hideLoading() {
    $('.spinner').hide();
} 