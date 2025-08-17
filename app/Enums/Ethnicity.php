<?php

namespace App\Enums;

enum Ethnicity: string
{
    case KINH = 'kinh';
    case TAY = 'tay';
    case THAI = 'thai';
    case MUONG = 'muong';
    case KHMER = 'khmer';
    case HOA = 'hoa';
    case NUNG = 'nung';
    case HMONG = 'hmong';
    case DAO = 'dao';
    case GIA_RAI = 'gia_rai';
    case NGAI = 'ngai';
    case EDE = 'ede';
    case BA_NA = 'ba_na';
    case XO_DANG = 'xo_dang';
    case SAN_CHAY = 'san_chay';
    case CO_HO = 'co_ho';
    case CHAM = 'cham';
    case SAN_DIU = 'san_diu';
    case HRE = 'hre';
    case MNONG = 'mnong';
    case RA_GLAI = 'ra_glai';
    case XTIENG = 'xtieng';
    case BRU_VAN_KIEU = 'bru_van_kieu';
    case THO = 'tho';
    case GIAY = 'giay';
    case CO_TU = 'co_tu';
    case GIE_TRIENG = 'gie_trieng';
    case MA = 'ma';
    case KHO_MU = 'kho_mu';
    case CO = 'co';
    case TA_OI = 'ta_oi';
    case CHO_RO = 'cho_ro';
    case KHANH = 'khanh';
    case XINH_MUN = 'xinh_mun';
    case HA_NHI = 'ha_nhi';
    case CHU_RU = 'chu_ru';
    case LAO = 'lao';
    case LA_CHI = 'la_chi';
    case LA_HA = 'la_ha';
    case PU_PEO = 'pu_peo';
    case BO_Y = 'bo_y';
    case LA_HU = 'la_hu';
    case CONG = 'cong';
    case SI_LA = 'si_la';
    case MANG = 'mang';
    case PA_THEN = 'pa_then';
    case CO_LAO = 'co_lao';
    case CONG_CONG = 'cong_cong';
    case LO_LO = 'lo_lo';
    case CHUT = 'chut';
    case MIEU = 'mieu';
    case BRAU = 'brau';
    case O_DU = 'o_du';
    case ROMA = 'roma';
    case OTHER = 'other';

    /**
     * Lấy tên hiển thị của dân tộc
     */
    public function label(): string
    {
        return match($this) {
            self::KINH => 'Kinh',
            self::TAY => 'Tày',
            self::THAI => 'Thái',
            self::MUONG => 'Mường',
            self::KHMER => 'Khmer',
            self::HOA => 'Hoa',
            self::NUNG => 'Nùng',
            self::HMONG => 'H\'Mông',
            self::DAO => 'Dao',
            self::GIA_RAI => 'Gia Rai',
            self::NGAI => 'Ngái',
            self::EDE => 'Ê Đê',
            self::BA_NA => 'Ba Na',
            self::XO_DANG => 'Xơ Đăng',
            self::SAN_CHAY => 'Sán Chay',
            self::CO_HO => 'Cơ Ho',
            self::CHAM => 'Chăm',
            self::SAN_DIU => 'Sán Dìu',
            self::HRE => 'Hrê',
            self::MNONG => 'M\'Nông',
            self::RA_GLAI => 'Ra Glai',
            self::XTIENG => 'Xtiêng',
            self::BRU_VAN_KIEU => 'Bru-Vân Kiều',
            self::THO => 'Thổ',
            self::GIAY => 'Giáy',
            self::CO_TU => 'Cơ Tu',
            self::GIE_TRIENG => 'Giẻ Triêng',
            self::MA => 'Mạ',
            self::KHO_MU => 'Khơ Mú',
            self::CO => 'Cờ',
            self::TA_OI => 'Tà Ôi',
            self::CHO_RO => 'Chơ Ro',
            self::KHANH => 'Kháng',
            self::XINH_MUN => 'Xinh Mun',
            self::HA_NHI => 'Hà Nhì',
            self::CHU_RU => 'Chu Ru',
            self::LAO => 'Lào',
            self::LA_CHI => 'La Chí',
            self::LA_HA => 'La Ha',
            self::PU_PEO => 'Pu Péo',
            self::BO_Y => 'Bố Y',
            self::LA_HU => 'La Hủ',
            self::CONG => 'Cống',
            self::SI_LA => 'Si La',
            self::MANG => 'Mảng',
            self::PA_THEN => 'Pà Thẻn',
            self::CO_LAO => 'Cơ Lao',
            self::CONG_CONG => 'Cống Cống',
            self::LO_LO => 'Lô Lô',
            self::CHUT => 'Chứt',
            self::MIEU => 'Miều',
            self::BRAU => 'Brâu',
            self::O_DU => 'Ơ Đu',
            self::ROMA => 'Rơ Măm',
            self::OTHER => 'Khác',
        };
    }

    /**
     * Lấy danh sách tất cả giá trị enum dưới dạng mảng
     */
    public static function toArray(): array
    {
        $result = [];
        foreach (self::cases() as $case) {
            $result[$case->value] = $case->label();
        }
        return $result;
    }

    /**
     * Chuyển đổi từ string sang enum
     */
    public static function fromString(?string $value): ?self
    {
        if ($value === null) return null;
        
        foreach (self::cases() as $case) {
            if ($case->value === $value) {
                return $case;
            }
        }
        
        return null;
    }

    /**
     * Lấy danh sách cho select options
     */
    public static function getSelectOptions(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[] = [
                'value' => $case->value,
                'label' => $case->label()
            ];
        }
        return $options;
    }

    /**
     * Kiểm tra có phải là "Khác" không
     */
    public function isOther(): bool
    {
        return $this === self::OTHER;
    }
}
