<?php
if (!function_exists('mobile_get_lang')) {
    function mobile_get_lang() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $lang = '';

        if (isset($_GET['lang']) && is_string($_GET['lang'])) {
            $lang = strtolower(trim($_GET['lang']));
        } elseif (isset($_POST['lang']) && is_string($_POST['lang'])) {
            $lang = strtolower(trim($_POST['lang']));
        } elseif (isset($_SESSION['mobile_lang']) && is_string($_SESSION['mobile_lang'])) {
            $lang = strtolower(trim($_SESSION['mobile_lang']));
        }

        if (!in_array($lang, ['ar', 'en'], true)) {
            $lang = 'en';
        }

        $_SESSION['mobile_lang'] = $lang;

        return $lang;
    }
}

if (!function_exists('mobile_t')) {
    function mobile_t($key, $lang = null) {
        if ($lang === null) {
            $lang = mobile_get_lang();
        }

        $translations = [
            'en' => [
                'page_title.orders' => 'Orders',
                'page_title.history' => 'History',
                'page_title.order_detail' => 'Order Detail',
                'empty_orders' => 'There is no orders.',
                'confirm' => 'Confirm',
                'barcode' => 'Barcode',
                'awb' => 'AWB',
                'receiver_name' => 'Receiver name',
                'receiver_phone' => 'Receiver phone',
                'city' => 'City',
                'area' => 'Area',
                'address' => 'Address',
                'payment_method' => 'Payment method',
                'cod' => 'COD',
                'pieces' => 'Pieces',
                'client_name' => 'Client name',
                'description' => 'Description',
                'notes' => 'Notes',
                'open_location' => 'Open Location',
                'delivered' => 'Delivered',
                'not_delivered' => 'Not delivered',
                'call' => 'Call',
                'whatsapp' => 'WhatsApp',
                'swal.confirm_title' => 'Are you sure?',
                'swal.confirm_text' => 'Have you actually received this order:',
                'swal.cancel' => 'No',
                'swal.confirm' => 'Yes, confirm it!',
                'swal.deliver_title' => 'Are you sure',
                'swal.deliver_text' => 'Order delivered?',
                'swal.deliver_confirm' => 'Yes, delivered.',
                'swal.reason_title' => 'Choose the reason',
                'swal.reason_cancel' => 'Cancel',
                'swal.reason_submit' => 'Submit',
                'swal.reason_error' => 'You did not choose the reason',
                'swal.error_title' => 'Error!',
                'swal.error_text' => 'Not all transactions were saved!',
                'swal.error_ok' => 'Cool',
                'swal.reason_0' => '--Select--',
                'swal.reason_1' => 'Customer does not answer',
                'swal.reason_2' => 'The customer cancelled the order',
                'swal.reason_3' => 'I couldnt understand the customer.',
                'swal.reason_4' => 'The customer is currently unavailable.',
                'swal.reason_5' => 'Customer location is not clear',
                'swal.reason_6' => 'Customer mobile number is incorrect',
                'swal.reason_7' => 'The customer changed the delivery date.',
                'swal.reason_8' => 'Application problem',
                'swal.reason_9' => 'Internet problem',
                'swal.reason_10' => 'car problem',
                'swal.reason_11' => 'Packaging problem',
                'swal.reason_12' => 'I have special problem',
                'swal.reason_13' => 'I am sick',
                'swal.reason_14' => 'Other',
            ],
            'ar' => [
                'page_title.orders' => 'الطلبات',
                'page_title.history' => 'السجل',
                'page_title.order_detail' => 'تفاصيل الطلب',
                'empty_orders' => 'لا توجد طلبات.',
                'confirm' => 'تأكيد',
                'barcode' => 'الباركود',
                'awb' => 'رقم الشحنة',
                'receiver_name' => 'اسم المستلم',
                'receiver_phone' => 'هاتف المستلم',
                'city' => 'المدينة',
                'area' => 'المنطقة',
                'address' => 'العنوان',
                'payment_method' => 'طريقة الدفع',
                'cod' => 'الدفع عند التسليم',
                'pieces' => 'القطع',
                'client_name' => 'اسم العميل',
                'description' => 'الوصف',
                'notes' => 'الملاحظات',
                'open_location' => 'فتح الموقع',
                'delivered' => 'تم التسليم',
                'not_delivered' => 'لم يتم التسليم',
                'call' => 'مكالمة',
                'whatsapp' => 'واتساب',
                'swal.confirm_title' => 'هل أنت متأكد؟',
                'swal.confirm_text' => 'هل استلمت هذا الطلب بالفعل:',
                'swal.cancel' => 'لا',
                'swal.confirm' => 'نعم، أكد الطلب!',
                'swal.deliver_title' => 'هل أنت متأكد',
                'swal.deliver_text' => 'هل تم تسليم الطلب؟',
                'swal.deliver_confirm' => 'نعم، تم التسليم.',
                'swal.reason_title' => 'اختر السبب',
                'swal.reason_cancel' => 'إلغاء',
                'swal.reason_submit' => 'إرسال',
                'swal.reason_error' => 'لم تختَر السبب',
                'swal.error_title' => 'خطأ!',
                'swal.error_text' => 'لم يتم حفظ جميع العمليات!',
                'swal.error_ok' => 'حسناً',
                'swal.reason_0' => '--اختر--',
                'swal.reason_1' => 'العميل لا يجيب',
                'swal.reason_2' => 'العميل ألغى الطلب',
                'swal.reason_3' => 'لم أتمكن من فهم العميل.',
                'swal.reason_4' => 'العميل غير متوفر حالياً.',
                'swal.reason_5' => 'موقع العميل غير واضح',
                'swal.reason_6' => 'رقم جوال العميل غير صحيح',
                'swal.reason_7' => 'غيّر العميل تاريخ التسليم.',
                'swal.reason_8' => 'مشكلة في التطبيق',
                'swal.reason_9' => 'مشكلة في الإنترنت',
                'swal.reason_10' => 'مشكلة في السيارة',
                'swal.reason_11' => 'مشكلة في التغليف',
                'swal.reason_12' => 'لدي مشكلة خاصة',
                'swal.reason_13' => 'أنا مريض',
                'swal.reason_14' => 'أخرى',
            ],
        ];

        return $translations[$lang][$key] ?? $translations['en'][$key] ?? $key;
    }
}

if (!function_exists('mobile_dir')) {
    function mobile_dir($lang = null) {
        if ($lang === null) {
            $lang = mobile_get_lang();
        }
        return $lang === 'ar' ? 'rtl' : 'ltr';
    }
}
?>
