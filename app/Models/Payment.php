<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'enrollment_id',
        'amount',
        'payment_date',
        'payment_method',
        'transaction_reference',
        'status',
        'notes'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date'
    ];

    /**
     * Quan hệ với ghi danh
     */
    public function enrollment()
    {
        return $this->belongsTo(Enrollment::class);
    }

    /**
     * Quan hệ với học viên thông qua ghi danh
     */
    public function student()
    {
        return $this->hasOneThrough(Student::class, Enrollment::class, 'id', 'id', 'enrollment_id', 'student_id');
    }

    /**
     * Quan hệ với lớp học thông qua ghi danh
     */
    public function courseClass()
    {
        return $this->hasOneThrough(CourseClass::class, Enrollment::class, 'id', 'id', 'enrollment_id', 'course_class_id');
    }

    /**
     * Scope cho các thanh toán đã xác nhận
     */
    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    /**
     * Scope cho các thanh toán chờ xác nhận
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope tìm kiếm theo phương thức thanh toán
     */
    public function scopeByMethod($query, $method)
    {
        return $query->where('payment_method', $method);
    }
    
    /**
     * Chuyển số tiền sang chữ
     */
    public function amountInWords()
    {
        $amount = (int) $this->amount;
        
        if ($amount == 0) {
            return 'Không đồng';
        }
        
        $units = ['', 'một', 'hai', 'ba', 'bốn', 'năm', 'sáu', 'bảy', 'tám', 'chín'];
        $teens = ['', 'mười một', 'mười hai', 'mười ba', 'mười bốn', 'mười lăm', 'mười sáu', 'mười bảy', 'mười tám', 'mười chín'];
        $tens = ['', 'mười', 'hai mươi', 'ba mươi', 'bốn mươi', 'năm mươi', 'sáu mươi', 'bảy mươi', 'tám mươi', 'chín mươi'];
        $groups = ['', 'nghìn', 'triệu', 'tỷ', 'nghìn tỷ', 'triệu tỷ'];
        
        $result = '';
        $groupCount = 0;
        
        while ($amount > 0) {
            $group = $amount % 1000;
            $amount = floor($amount / 1000);
            
            if ($group > 0) {
                $groupText = '';
                
                // Xử lý hàng trăm
                $hundred = floor($group / 100);
                if ($hundred > 0) {
                    $groupText .= $units[$hundred] . ' trăm ';
                    $group %= 100;
                    
                    // Nếu hàng chục và đơn vị đều là 0
                    if ($group == 0 && $amount > 0) {
                        $groupText .= ' ';
                    }
                }
                
                // Xử lý hàng chục và đơn vị
                if ($group > 0) {
                    if ($group < 10) {
                        // Nếu có hàng trăm và hàng đơn vị (không có hàng chục)
                        if ($hundred > 0) {
                            $groupText .= 'lẻ ';
                        }
                        $groupText .= $units[$group];
                    } elseif ($group < 20) {
                        $groupText .= $teens[$group - 10];
                    } else {
                        $ten = floor($group / 10);
                        $unit = $group % 10;
                        $groupText .= $tens[$ten];
                        if ($unit > 0) {
                            // Đặc biệt khi hàng đơn vị là "một" và "năm"
                            if ($unit == 1) {
                                $groupText .= ' mốt';
                            } elseif ($unit == 5) {
                                $groupText .= ' lăm';
                            } else {
                                $groupText .= ' ' . $units[$unit];
                            }
                        }
                    }
                }
                
                if ($groupCount > 0) {
                    $groupText .= ' ' . $groups[$groupCount] . ' ';
                }
                
                $result = $groupText . $result;
            }
            
            $groupCount++;
        }
        
        return ucfirst(trim($result)) . ' đồng';
    }
}
