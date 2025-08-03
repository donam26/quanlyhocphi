<?php

if (!function_exists('formatCurrency')) {
    /**
     * Định dạng số tiền theo định dạng tiền tệ Việt Nam
     *
     * @param float $number Số tiền cần định dạng
     * @return string Chuỗi đã định dạng theo tiền tệ
     */
    function formatCurrency($number)
    {
        return number_format($number, 0, ',', '.'); 
    }
} 