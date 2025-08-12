import './bootstrap';
import Sortable from 'sortablejs';
window.Sortable = Sortable;

// Cấu hình datepicker toàn cục
$.fn.datepicker.defaults.format = 'dd/mm/yyyy';
$.fn.datepicker.defaults.autoclose = true;
$.fn.datepicker.defaults.language = 'vi';

// Khởi tạo datepicker cho các phần tử input có class 'datepicker'
$(document).ready(function() {
    $('.datepicker').datepicker();
    
    // Global handler để convert payment_date từ YYYY-MM-DD sang dd/mm/yyyy
    // trước khi gửi API requests
    $(document).on('submit', 'form', function(e) {
        const form = this;
        const paymentDateInput = form.querySelector('input[name="payment_date"][type="date"]');
        
        if (paymentDateInput && paymentDateInput.value) {
            const dateValue = paymentDateInput.value; // YYYY-MM-DD
            const [year, month, day] = dateValue.split('-');
            const ddmmyyyy = `${day}/${month}/${year}`;
            
            // Tạo hidden input với format đúng
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'payment_date';
            hiddenInput.value = ddmmyyyy;
            form.appendChild(hiddenInput);
            
            // Disable input gốc để không gửi đi
            paymentDateInput.disabled = true;
        }
    });
    
    // Handler cho AJAX requests cần convert payment_date
    $(document).ajaxSend(function(event, jqxhr, settings) {
        if (settings.data && settings.data instanceof FormData) {
            const paymentDate = settings.data.get('payment_date');
            if (paymentDate && /^\d{4}-\d{2}-\d{2}$/.test(paymentDate)) {
                const [year, month, day] = paymentDate.split('-');
                const ddmmyyyy = `${day}/${month}/${year}`;
                settings.data.set('payment_date', ddmmyyyy);
            }
        }
    });
});

// Code khác trong app.js...
