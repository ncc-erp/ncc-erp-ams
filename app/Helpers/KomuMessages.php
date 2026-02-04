<?php

namespace App\Helpers;

class KomuMessages
{
    public static function toolCheckout($data)
    {
        return "📋 **Thông báo giao thiết bị**\nBộ phận IT đã giao cho **{$data['user_name']}**: {$data['count']} thiết bị {$data['tool_name']}.\n🔗 Vui lòng xác nhận nhận thiết bị tại: {$data['link']}";
    }

    public static function assetCheckout($data)
    {
        return "📋 **Thông báo giao thiết bị**\nBộ phận IT đã giao cho **{$data['user_name']}**: {$data['count']} thiết bị {$data['asset_name']}.\n🔗 Vui lòng xác nhận nhận thiết bị tại: {$data['link']}";
    }
    
    public static function toolCheckin($data)
    {
        return "📥 **Thông báo thu hồi thiết bị**\nBộ phận IT đã thu hồi từ **{$data['user_name']}**: {$data['count']} thiết bị {$data['tool_name']}.";
    }

    public static function assetCheckin($data)
    {
        return "📥 **Thông báo thu hồi thiết bị**\n Bộ phận IT đã thu hồi từ **{$data['user_name']}**: {$data['count']} thiết bị {$data['asset_name']}.";
    }
    
    public static function toolCheckoutDigitalSignature($data)
    {
        return "🔐 **Thông báo giao USB Token**\nBộ phận IT đã giao cho **{$data['user_name']}**: {$data['count']} USB Token {$data['signature_name']}.";
    }
    
    public static function toolCheckinDigitalSignature($data)
    {
        return "🔐 **Thông báo thu hồi USB Token**\nBộ phận IT đã thu hồi từ **{$data['user_name']}**: {$data['count']} USB Token {$data['signature_name']}.";
    }

    public static function softwareCheckout($data)
    {
        return "💻 **Thông báo cấp phát phần mềm**\nBộ phận IT đã giao cho **{$data['user_name']}**: {$data['count']} key active phần mềm {$data['software_name']}.";
    }

    public static function confirmCheckinDigitalSignature($data)
    {
        return "✅ **Xác nhận Token thuế**\nNgười dùng **{$data['user_name']}** đã {$data['is_confirm']} {$data['signatures_count']} Token thuế có serial: **{$data['seri']}**.";
    }
    
    public static function confirmCheckout($data)
    {
        return "✅ **Xác nhận nhận thiết bị**\nNgười dùng **{$data['user_name']}** đã xác nhận nhận {$data['count']} thiết bị {$data['tool_name']}.";
    }

    public static function assetConfirmCheckout($data)
    {
        return "✅ **Xác nhận nhận thiết bị**\nNgười dùng **{$data['user_name']}** đã {$data['is_confirm']} {$data['asset_count']} thiết bị {$data['asset_name']}.";
    }
    
    public static function confirmCheckin($data)
    {
        return "✅ **Xác nhận trả thiết bị**\nNgười dùng **{$data['user_name']}** đã xác nhận trả lại {$data['count']} thiết bị {$data['tool_name']}.";
    }
    
    public static function confirmRevoke($data)
    {
        return "✅ **Xác nhận thu hồi**\nNgười dùng **{$data['user_name']}** đã xác nhận thu hồi {$data['count']} thiết bị {$data['tool_name']}.";
    }
    
    public static function assetConfirmRevoke($data)
    {
        return "✅ **Xác nhận thu hồi thiết bị**\nNgười dùng **{$data['user_name']}** đã {$data['is_confirm']} {$data['asset_count']} thiết bị {$data['asset_name']}.";
    }
    
    public static function confirmToolCheckout($data)
    {
        return "✅ **Xác nhận nhận thiết bị từ IT**\nNgười dùng **{$data['user_name']}** đã xác nhận nhận {$data['count']} thiết bị {$data['tool_name']} từ bộ phận IT.";
    }
    
    public static function confirmCheckoutDigitalSignature($data)
    {
        return "✅ **Xác nhận nhận USB Token**\nNgười dùng **{$data['user_name']}** đã xác nhận nhận {$data['count']} USB Token {$data['signature_name']}.";
    }
    
    public static function rejectRevoke($data)
    {
        return "❌ **Từ chối thu hồi thiết bị**\nBộ phận IT đã từ chối thu hồi từ **{$data['user_name']}**: {$data['asset_count']} thiết bị {$data['asset_name']}.";
    }

    public static function rejectCheckout($data)
    {
        return "❌ **Từ chối giao thiết bị**\nBộ phận IT đã từ chối giao {$data['count']} thiết bị {$data['tool_name']} cho **{$data['user_name']}**.";
    }

    public static function rejectCheckin($data)
    {
        return "❌ **Từ chối thu hồi thiết bị**\nBộ phận IT đã từ chối thu hồi {$data['count']} thiết bị {$data['tool_name']} từ **{$data['user_name']}**.";
    }

    public static function rejectCheckoutDigitalSignature($data)
    {
        return "❌ **Từ chối giao USB Token**\nBộ phận IT đã từ chối giao {$data['count']} USB Token {$data['signature_name']} cho **{$data['user_name']}**.";
    }

    public static function rejectCheckinDigitalSignature($data)
    {
        return "❌ **Từ chối thu hồi USB Token**\nBộ phận IT đã từ chối thu hồi {$data['count']} USB Token {$data['signature_name']} từ **{$data['user_name']}**.";
    }

    public static function rejectAllocate($data)
    {
        return "❌ **Từ chối phân bổ thiết bị**\nBộ phận IT đã từ chối phân bổ {$data['count']} thiết bị {$data['tool_name']} cho **{$data['user_name']}**.";
    }

    public static function assetRejectAllocate($data)
    {
        return "❌ **Từ chối/Xác nhận thiết bị**\nNgười dùng **{$data['user_name']}** đã {$data['is_confirm']} {$data['asset_count']} thiết bị {$data['asset_name']}.";
    }
}
