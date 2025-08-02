<?php

namespace App\Traits;

use Carbon\Carbon;
use DateTimeInterface;

trait Date
{
    /**
     * Format ngày tháng khi trả về JSON
     *
     * @param  \DateTimeInterface  $date
     * @return string
     */
    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format(config('app.date_format', 'd/m/Y'));
    }
    
    /**
     * Format một trường ngày tháng theo định dạng dd/mm/yyyy
     *
     * @param  string  $attribute
     * @return string
     */
    public function formatDate($attribute)
    {
        if (!$this->$attribute) {
            return '';
        }
        
        return $this->$attribute instanceof Carbon 
            ? $this->$attribute->format(config('app.date_format', 'd/m/Y')) 
            : Carbon::parse($this->$attribute)->format(config('app.date_format', 'd/m/Y'));
    }
    
    /**
     * Format một trường ngày giờ theo định dạng dd/mm/yyyy H:i:s
     *
     * @param  string  $attribute
     * @return string
     */
    public function formatDateTime($attribute)
    {
        if (!$this->$attribute) {
            return '';
        }
        
        return $this->$attribute instanceof Carbon 
            ? $this->$attribute->format(config('app.datetime_format', 'd/m/Y H:i:s'))
            : Carbon::parse($this->$attribute)->format(config('app.datetime_format', 'd/m/Y H:i:s'));
    }
    
    /**
     * Chuyển đổi chuỗi ngày tháng từ định dạng dd/mm/yyyy sang Y-m-d để lưu vào database
     *
     * @param  string  $value
     * @return string
     */
    public static function parseDate($value)
    {
        if (empty($value)) {
            return null;
        }
        
        // Kiểm tra xem chuỗi đã ở định dạng Y-m-d chưa
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return $value;
        }
        
        // Chuyển đổi từ định dạng d/m/Y sang Y-m-d
        $parts = explode('/', $value);
        if (count($parts) === 3) {
            $day = $parts[0];
            $month = $parts[1];
            $year = $parts[2];
            
            return sprintf('%s-%s-%s', $year, $month, $day);
        }
        
        // Nếu không thể xử lý, trả về nguyên giá trị
        return $value;
    }
} 