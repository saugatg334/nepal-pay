$(document).ready(function() {
    $.ajaxSetup({
        headers: {
            'X-CSRF-Token': $('input[name="csrf_token"]').val()
        }
    });
    
    $('.alert').alert();
    
    $('[data-toggle="tooltip"]').tooltip();
    
    $('.confirm-delete').on('click', function(e) {
        if (!confirm('Are you sure you want to delete this?')) {
            e.preventDefault();
        }
    });
    
    $('.amount-input').on('input', function() {
        var value = $(this).val();
        value = value.replace(/[^\d.]/g, '');
        $(this).val(value);
    });
    
    $('.phone-input').on('input', function() {
        var value = $(this).val();
        value = value.replace(/[^0-9]/g, '');
        $(this).val(value);
    });
    
    $('.search-box').on('keyup', function() {
        var value = $(this).val().toLowerCase();
        $('.searchable-item').filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
        });
    });
    
    $('.quick-amount').on('click', function() {
        var amount = $(this).data('amount');
        $('#amount').val(amount);
    });
    
    $(window).on('scroll', function() {
        if ($(this).scrollTop() > 100) {
            $('.scroll-top').fadeIn();
        } else {
            $('.scroll-top').fadeOut();
        }
    });
    
    $('.scroll-top').on('click', function() {
        $('html, body').animate({ scrollTop: 0 }, 300);
    });
    
    // Beneficiary favorite toggle
    $(document).on('click', '.toggle-fav', function(e) {
        e.preventDefault();
        var btn = $(this);
        var id = btn.data('id');
        var isFav = btn.data('fav');
        
        $.ajax({
            url: '<?php echo APP_URL; ?>/index.php?page=toggle_favorite',
            method: 'POST',
            data: {
                id: id,
                _token: $('input[name="csrf_token"]').val() || ''
            },
            success: function(response) {
                if (response.success) {
                    btn.data('fav', isFav ? 0 : 1);
                    btn.find('i').removeClass('fa-star fa-star-o')
                        .addClass(isFav ? 'fa-star-o' : 'fa-star');
                    btn.data('fav', !isFav);
                    if (response.message) showAlert(response.message, 'success');
                }
            },
            error: function() {
                showAlert('Error toggling favorite', 'error');
            }
        });
    });
    
    function formatCurrency(amount) {
        return 'NPR ' + parseFloat(amount).toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }
    
    $(document).on('click', '.load-more', function() {
        var button = $(this);
        var page = parseInt(button.data('page')) + 1;
        var container = button.closest('.load-more-container');
        
        $.ajax({
            url: button.data('url'),
            type: 'GET',
            data: { page: page },
            beforeSend: function() {
                button.prop('disabled', true).text('Loading...');
            },
            success: function(response) {
                if (response.data && response.data.length > 0) {
                    container.before(response.html);
                    button.data('page', page);
                    button.prop('disabled', false).text('Load More');
                } else {
                    button.remove();
                }
            },
            error: function() {
                button.prop('disabled', false).text('Load More');
                alert('Error loading more items');
            }
        });
    });
    
    $(document).on('submit', 'form[data-ajax]', function(e) {
        e.preventDefault();
        var form = $(this);
        var url = form.attr('action');
        var method = form.attr('method') || 'POST';
        
        $.ajax({
            url: url,
            type: method,
            data: form.serialize(),
            beforeSend: function() {
                form.find('button[type="submit"]').prop('disabled', true).text('Processing...');
            },
            success: function(response) {
                if (response.success) {
                    if (response.message) {
                        showAlert(response.message, 'success');
                    }
                    if (response.redirect) {
                        window.location.href = response.redirect;
                    }
                } else {
                    showAlert(response.message || 'Error occurred', 'error');
                }
            },
            error: function() {
                showAlert('Error processing request', 'error');
            },
            complete: function() {
                form.find('button[type="submit"]').prop('disabled', false).text('Submit');
            }
        });
    });
    
    function showAlert(message, type) {
        var alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        var html = '<div class="alert ' + alertClass + ' alert-dismissible fade show" role="alert">' +
            message +
            '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
            '</div>';
        
        $('.container').prepend(html);
        
        setTimeout(function() {
            $('.alert').first().alert('close');
        }, 5000);
    }
    
    $(document).on('click', '.qr-scanner-trigger', function() {
        $('.qr-scanner-modal').modal('show');
    });
    
    var clipboard = new ClipboardJS('.copy-btn');
    
    clipboard.on('success', function(e) {
        showAlert('Copied to clipboard', 'success');
    });
    
    $('.refresh-token').on('click', function(e) {
        e.preventDefault();
        $.get($(this).attr('href'), function(response) {
            if (response.token) {
                $('input[name="csrf_token"]').val(response.token);
                showAlert('Token refreshed', 'success');
            }
        });
    });
});

function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        alert('Copied to clipboard');
    });
}

function showLoader() {
    $('.page-loader').show();
}

function hideLoader() {
    $('.page-loader').hide();
}

function formatAmount(amount) {
    return 'NPR ' + parseFloat(amount).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
}

function parseAmount(amount) {
    return parseFloat(amount.replace(/[^0-9.-]/g, ''));
}