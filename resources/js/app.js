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
});

// Code khác trong app.js...
