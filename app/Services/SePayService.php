<?php

namespace App\Services;

use App\Models\Payment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class SePayService
{
    protected $apiUrl;
    protected $apiToken;
    protected $pattern;
    protected $bankNumber;
    protected $bankName;
    protected $bankCode;
    protected $accountOwner;
    protected $vietqrUrl;

    public function __construct()
    {
        $this->apiUrl = config('sepay.api_url');
        $this->apiToken = config('sepay.api_token');
        $this->pattern = config('sepay.pattern');
        $this->bankNumber = config('sepay.bank_number');
        $this->bankName = config('sepay.bank_name');
        $this->bankCode = config('sepay.bank_code');
        $this->accountOwner = config('sepay.account_owner');
        $this->vietqrUrl = config('sepay.vietqr_url');
    }

    /**
     * Tạo mã QR cho thanh toán
     */
    public function generateQR(Payment $payment)
    {
        try {
            // Đảm bảo payment có enrollment được load
            if (!$payment->relationLoaded('enrollment') && $payment->enrollment_id) {
                $payment->load('enrollment.student', 'enrollment.courseItem');
            }
            
            // Tạo transaction ID đơn giản: SEVQR{payment_id}
            $transactionId = $this->pattern . $payment->id;
            
            $amount = $payment->amount;
            
            // Log thông tin để debug
            Log::info('Generating QR code', [
                'payment_id' => $payment->id,
                'transaction_id' => $transactionId,
                'amount' => $amount,
                'bank_code' => $this->bankCode,
                'bank_number' => $this->bankNumber
            ]);
            
            // Sử dụng URL trực tiếp của SePay với format đúng
            $qrImageUrl = 'https://qr.sepay.vn/img?' . http_build_query([
                'acc' => $this->bankNumber,
                'bank' => $this->bankCode,
                'amount' => $amount,
                'des' => $transactionId,
                'template' => 'compact'
            ]);
            
            Log::info('Generated SePay QR URL', ['url' => $qrImageUrl]);
            
            return [
                'success' => true,
                'data' => [
                    'qr_image' => $qrImageUrl,
                    'bank_name' => $this->bankName,
                    'bank_account' => $this->bankNumber,
                    'account_owner' => $this->accountOwner,
                    'amount' => $payment->amount,
                    'content' => $transactionId
                ]
            ];
            
        } catch (\Exception $e) {
            Log::error('Error generating QR code: ' . $e->getMessage(), [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Fallback to VietQR khi có lỗi
            try {
                $transactionId = null;
                if ($payment->enrollment && $payment->enrollment->student && $payment->enrollment->courseItem) {
                    $transactionId = $this->pattern . $payment->enrollment->student->id . '_' . $payment->enrollment->courseItem->id;
                } else {
                    $transactionId = 'PAY' . str_pad($payment->id, 6, '0', STR_PAD_LEFT);
                }
                
                // Tạo chuỗi QR đơn giản theo định dạng <số tài khoản>|<ngân hàng>|<tên chủ tài khoản>|<số tiền>|<nội dung>
                $qrString = implode('|', [
                    $this->bankNumber,
                    $this->bankName,
                    $this->accountOwner,
                    $payment->amount,
                    $transactionId
                ]);
                
                return [
                    'success' => true,
                    'data' => [
                        'qr_string' => $qrString,
                        'bank_name' => $this->bankName,
                        'bank_account' => $this->bankNumber,
                        'account_owner' => $this->accountOwner,
                        'amount' => $payment->amount,
                        'content' => $transactionId,
                        'fallback' => true,
                        'manual' => true
                    ]
                ];
            } catch (\Exception $e2) {
                Log::error('Error in fallback QR generation: ' . $e2->getMessage());
                return [
                    'success' => false,
                    'message' => 'Không thể tạo mã QR. Vui lòng sử dụng thông tin chuyển khoản thủ công.',
                    'data' => [
                        'bank_name' => $this->bankName,
                        'bank_account' => $this->bankNumber,
                        'account_owner' => $this->accountOwner,
                        'amount' => $payment->amount,
                        'content' => $transactionId ?? ''
                    ]
                ];
            }
        }
    }

    /**
     * Tạo mã QR thông qua API SePay
     */
    private function createQrViaSePay($transactionId, $amount)
    {
        try {
            // Sử dụng cấu trúc URL mới của SePay
            $qrImageUrl = 'https://qr.sepay.vn/img?acc=' . $this->bankNumber 
                . '&bank=' . $this->bankCode 
                . '&amount=' . $amount 
                . '&des=' . urlencode($transactionId)
                . '&template=compact';
            
            Log::info('Generated SePay QR URL', ['url' => $qrImageUrl]);
            
            return [
                'success' => true,
                'data' => [
                    'qr_image' => $qrImageUrl,
                    'bank_name' => $this->bankName,
                    'bank_account' => $this->bankNumber,
                    'account_owner' => $this->accountOwner,
                    'amount' => $amount,
                    'content' => $transactionId
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Error generating SePay QR: ' . $e->getMessage(), [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'message' => 'Không thể tạo mã QR qua SePay: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Tạo mã QR thông qua API VietQR (fallback)
     */
    private function createQrViaVietQR($transactionId, $amount)
    {
        try {
            // Kiểm tra URL API VietQR
            if (empty($this->vietqrUrl)) {
                throw new \Exception('URL API VietQR không được cấu hình');
            }
            
            // Chuẩn bị dữ liệu gửi đến API VietQR theo tài liệu chính thức
            $data = [
                'accountNo' => $this->bankNumber,
                'accountName' => $this->accountOwner,
                'acqId' => $this->bankCode,
                'amount' => $amount,
                'addInfo' => $transactionId,
                'format' => 'compact'
            ];
            
            Log::info('Calling VietQR API', ['request' => $data, 'url' => $this->vietqrUrl]);
            
            // Gọi API VietQR để tạo mã QR với timeout 10 giây
            $response = Http::timeout(10)
                ->withHeaders([
                    'x-api-key' => config('sepay.vietqr_api_key', 'we-l0v3-v1et-qr'),
                    'Content-Type' => 'application/json'
                ])
                ->post($this->vietqrUrl, $data);
                
            $result = $response->json();
            
            Log::info('VietQR API Response', ['response' => $result]);
            
            // Kiểm tra kết quả từ API theo định dạng trả về của VietQR
            if (isset($result['code']) && $result['code'] === '00' && isset($result['data']['qrDataURL'])) {
                return [
                    'success' => true,
                    'data' => [
                        'qr_image' => $result['data']['qrDataURL'],
                        'bank_name' => $this->bankName,
                        'bank_account' => $this->bankNumber,
                        'account_owner' => $this->accountOwner,
                        'amount' => $amount,
                        'content' => $transactionId,
                        'fallback' => true
                    ]
                ];
            }
            
            // Nếu API không trả về kết quả mong muốn, chuyển sang phương thức tạo QR thủ công
            throw new \Exception('API VietQR không trả về dữ liệu QR: ' . json_encode($result));
            
        } catch (\Exception $e) {
            Log::error('Error generating VietQR: ' . $e->getMessage(), [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Tạo mã QR theo phương thức thủ công khi API lỗi
            try {
                // Tạo chuỗi QR đơn giản theo định dạng <số tài khoản>|<ngân hàng>|<tên chủ tài khoản>|<số tiền>|<nội dung>
                $qrString = implode('|', [
                    $this->bankNumber,
                    $this->bankName,
                    $this->accountOwner,
                    $amount,
                    $transactionId
                ]);
                
                return [
                    'success' => true,
                    'data' => [
                        'qr_string' => $qrString,
                        'bank_name' => $this->bankName,
                        'bank_account' => $this->bankNumber,
                        'account_owner' => $this->accountOwner,
                        'amount' => $amount,
                        'content' => $transactionId,
                        'fallback' => true,
                        'manual' => true
                    ]
                ];
            } catch (\Exception $e2) {
                Log::error('Error generating manual QR: ' . $e2->getMessage(), [
                    'error' => $e2->getMessage(),
                    'trace' => $e2->getTraceAsString()
                ]);
                
                // Tạo thông tin chuyển khoản thủ công khi mọi phương thức đều thất bại
                return [
                    'success' => false,
                    'message' => 'Không thể tạo mã QR. Vui lòng sử dụng thông tin chuyển khoản thủ công.',
                    'data' => [
                        'bank_name' => $this->bankName,
                        'bank_account' => $this->bankNumber,
                        'account_owner' => $this->accountOwner,
                        'amount' => $amount,
                        'content' => $transactionId,
                        'fallback' => true
                    ]
                ];
            }
        }
    }
    
    /**
     * Tạo chuỗi EMV QR Code theo chuẩn TCCS 03:2018/NHNNVN của NHNN Việt Nam
     * Tham khảo: https://www.vietqr.io/portal/vietqr/en/developer
     */
    private function generateEMVQrCode($transactionId, $amount)
    {
        try {
            // ID 00: Payload Format Indicator, giá trị cố định "01"
            $payloadFormatIndicator = $this->formatQrField('00', '01');
            
            // ID 01: Point of Initiation Method, giá trị "12" (dynamic QR) hoặc "11" (static QR)
            $pointOfInitiation = $this->formatQrField('01', '12'); // 12 là dynamic QR code (có số tiền)
            
            // ID 38: Merchant Account Information (Thông tin tài khoản đơn vị thụ hưởng)
            $merchantAccountInfo = $this->buildMerchantAccountInfo($this->bankCode, $this->bankNumber);
            
            // ID 52: Merchant Category Code (MCC) - Mã danh mục đơn vị chấp nhận thanh toán
            $merchantCategoryCode = $this->formatQrField('52', '0000'); // 0000 là mã chung
            
            // ID 53: Transaction Currency (Mã tiền tệ) - 704 là mã tiền Việt Nam đồng (VND)
            $currency = $this->formatQrField('53', '704');
            
            // ID 54: Transaction Amount (Số tiền giao dịch)
            $transactionAmount = '';
            if ($amount > 0) {
                $transactionAmount = $this->formatQrField('54', number_format($amount, 0, '', ''));
            }
            
            // ID 58: Country Code (Mã quốc gia) - VN là mã quốc gia Việt Nam
            $countryCode = $this->formatQrField('58', 'VN');
            
            // ID 59: Merchant Name (Tên đơn vị thụ hưởng)
            $merchantName = $this->formatQrField('59', $this->accountOwner);
            
            // ID 60: Merchant City (Thành phố của đơn vị thụ hưởng)
            $merchantCity = $this->formatQrField('60', 'HANOI'); // Giả sử đơn vị thụ hưởng ở Hà Nội
            
            // ID 62: Additional Data Field Template (Thông tin bổ sung)
            $additionalData = $this->formatQrField('62', $this->formatQrField('08', $transactionId)); // 08 là Reference Label
            
            // Kết hợp các thành phần để tạo chuỗi QR (chưa có CRC)
            $qrDataWithoutCrc = $payloadFormatIndicator . $pointOfInitiation . $merchantAccountInfo . 
                            $merchantCategoryCode . $currency . $transactionAmount . $countryCode . 
                            $merchantName . $merchantCity . $additionalData;
            
            // Thêm CRC-16 vào cuối chuỗi QR
            $crc = $this->calculateCRC16($qrDataWithoutCrc . '6304');
            $qrData = $qrDataWithoutCrc . $this->formatQrField('63', $crc);
            
            Log::info('EMV QR Code generated', ['qr_data' => $qrData]);
            
            return $qrData;
        } catch (\Exception $e) {
            Log::error('Error generating EMV QR Code: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Xây dựng thông tin tài khoản đơn vị thụ hưởng (Merchant Account Information)
     * Theo chuẩn VietQR
     */
    private function buildMerchantAccountInfo($bankCode, $accountNumber)
    {
        // Theo chuẩn VietQR:
        // ID 00: Global Unique Identifier (GUI)
        $guid = $this->formatQrField('00', 'A000000727'); // A000000727 là mã định danh VietQR
        
        // ID 01: Thông tin ngân hàng thụ hưởng
        $bankInfo = $this->formatQrField('01', $this->formatQrField('00', $bankCode) . 
                                              $this->formatQrField('01', $accountNumber));
        
        // ID 02: Service Code - 'QRIBFTTA' cho chuyển khoản tài khoản
        $serviceCode = $this->formatQrField('02', 'QRIBFTTA');
        
        // Kết hợp thông tin và trả về với ID 38
        $merchantAccountInfo = $guid . $bankInfo . $serviceCode;
        return $this->formatQrField('38', $merchantAccountInfo);
    }
    
    /**
     * Định dạng một trường trong chuỗi QR
     */
    private function formatQrField($id, $value)
    {
        $length = strlen($value);
        return $id . str_pad($length, 2, '0', STR_PAD_LEFT) . $value;
    }
    
    /**
     * Tính toán mã CRC-16 cho chuỗi QR theo chuẩn TCCS 03:2018/NHNNVN
     */
    private function calculateCRC16($data)
    {
        // CRC-16/CCITT-FALSE algorithm
        $crc = 0xFFFF;
        $polynomial = 0x1021;
        
        for ($i = 0; $i < strlen($data); $i++) {
            $crc ^= (ord($data[$i]) << 8);
            
            for ($j = 0; $j < 8; $j++) {
                if ($crc & 0x8000) {
                    $crc = (($crc << 1) ^ $polynomial) & 0xFFFF;
                } else {
                    $crc = ($crc << 1) & 0xFFFF;
                }
            }
        }
        
        return strtoupper(str_pad(dechex($crc), 4, '0', STR_PAD_LEFT));
    }

    /**
     * Kiểm tra trạng thái thanh toán của một giao dịch
     */
    public function checkPaymentStatus($transactionId)
    {
        try {
            $url = $this->apiUrl . '/transactions/' . $transactionId;
            
            $response = Http::withToken($this->apiToken)
                ->get($url);
            
            $result = $response->json();
            
            if (isset($result['success']) && $result['success'] && isset($result['data'])) {
                return [
                    'success' => true,
                    'confirmed' => $result['data']['status'] === 'completed',
                    'transaction_reference' => $result['data']['transaction_id'] ?? null,
                    'payment_date' => $result['data']['completed_at'] ?? null,
                    'amount' => $result['data']['amount'] ?? 0,
                ];
            }
            
            return [
                'success' => false,
                'confirmed' => false,
                'message' => $result['message'] ?? 'Không thể kiểm tra trạng thái thanh toán'
            ];
            
        } catch (\Exception $e) {
            Log::error('Error checking payment status: ' . $e->getMessage());
            
            return [
                'success' => false,
                'confirmed' => false,
                'message' => 'Lỗi khi kiểm tra trạng thái thanh toán: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Xác thực webhook từ SePay
     */
    public function validateWebhook($payload, $signature)
    {
        $calculatedSignature = hash_hmac('sha256', json_encode($payload), $this->apiToken);
        return hash_equals($signature, $calculatedSignature);
    }
    
    /**
     * Phân tích nội dung chuyển khoản để tìm mã giao dịch
     */
    public function parseTransactionContent($content)
    {
        // Tìm định dạng SEVQRXXX_YYY_ZZZ trong nội dung chuyển khoản
        if (preg_match('/' . $this->pattern . '(\d+)_(\d+)_(\d+)/', $content, $matches)) {
            return [
                'payment_id' => $matches[1],
                'student_id' => $matches[2],
                'course_id' => $matches[3],
            ];
        }

        // Fallback: Tìm định dạng cũ SEVQRXXX_YYY
        if (preg_match('/' . $this->pattern . '(\d+)_(\d+)/', $content, $matches)) {
            return [
                'student_id' => $matches[1],
                'course_id' => $matches[2],
            ];
        }
        
        // Tìm định dạng PAYXXXXXX trong nội dung chuyển khoản
        if (preg_match('/PAY(\d+)/', $content, $matches)) {
            return [
                'payment_id' => $matches[1],
            ];
        }
        
        return null;
    }
} 