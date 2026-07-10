function getCurrentLang(){
    const params = new URLSearchParams(window.location.search);
    const lang = params.get('lang');
    return lang === 'ar' ? 'ar' : 'en';
}

function t(key){
    const lang = getCurrentLang();
    const translations = {
        en: {
            confirmTitle: 'Are you sure?',
            confirmText: 'Have you actually received this order:',
            cancel: 'No',
            confirm: 'Yes, confirm it!',
            pickedTitle: 'Confirm pickup',
            pickedText: 'Mark this order as picked?',
            pickedConfirm: 'Yes, picked',
            deliverTitle: 'Are you sure',
            deliverText: 'Order delivered?',
            deliverConfirm: 'Yes, delivered.',
            reasonTitle: 'Choose the reason',
            reasonCancel: 'Cancel',
            reasonSubmit: 'Submit',
            reasonError: 'You did not choose the reason',
            errorTitle: 'Error!',
            errorText: 'Not all transactions were saved!',
            errorOk: 'Cool',
            errorBarcodeRequired: 'Shipment barcode is required.',
            errorPodRequired: 'Proof of delivery is required for credit orders.',
            errorBarcodeMismatch: 'Barcode does not match this order.',
            modalAwb: 'Order number',
            modalBarcode: 'Shipment barcode',
            modalBarcodeHint: 'Enter or scan the shipment barcode',
            modalPod: 'Proof of delivery (POD)',
            modalPodHint: 'Take a photo or choose a file',
            modalConfirm: 'Confirm',
            modalTitlePicked: 'Confirm pickup',
            modalTitleDelivered: 'Confirm delivery',
            modalTitleNotDelivered: 'Not delivered',
            reason0: '--Select--',
            reason1: 'Customer does not answer',
            reason2: 'The customer cancelled the order',
            reason3: 'I couldnt understand the customer.',
            reason4: 'The customer is currently unavailable.',
            reason5: 'Customer location is not clear',
            reason6: 'Customer mobile number is incorrect',
            reason7: 'The customer changed the delivery date.',
            reason8: 'Application problem',
            reason9: 'Internet problem',
            reason10: 'car problem',
            reason11: 'Packaging problem',
            reason12: 'I have special problem',
            reason13: 'I am sick',
            reason14: 'Other'
        },
        ar: {
            confirmTitle: 'هل أنت متأكد؟',
            confirmText: 'هل استلمت هذا الطلب بالفعل:',
            cancel: 'لا',
            confirm: 'نعم، أكد الطلب!',
            pickedTitle: 'تأكيد الالتقاط',
            pickedText: 'هل تريد تأكيد التقاط هذا الطلب؟',
            pickedConfirm: 'نعم، التقطت',
            deliverTitle: 'هل أنت متأكد',
            deliverText: 'هل تم تسليم الطلب؟',
            deliverConfirm: 'نعم، تم التسليم.',
            reasonTitle: 'اختر السبب',
            reasonCancel: 'إلغاء',
            reasonSubmit: 'إرسال',
            reasonError: 'لم تختَر السبب',
            errorTitle: 'خطأ!',
            errorText: 'لم يتم حفظ جميع العمليات!',
            errorOk: 'حسناً',
            errorBarcodeRequired: 'رقم الشحنة / الباركود مطلوب.',
            errorPodRequired: 'ملف إثبات التسليم مطلوب لطلبات الدفع الآجل (Credit).',
            errorBarcodeMismatch: 'الباركود لا يطابق رقم هذا الطلب.',
            modalAwb: 'رقم الطلب',
            modalBarcode: 'رقم الشحنة / الباركود',
            modalBarcodeHint: 'أدخل الرقم أو امسح الباركود',
            modalPod: 'ملف إثبات التسليم (POD)',
            modalPodHint: 'التقط صورة أو اختر ملفاً',
            modalConfirm: 'تأكيد',
            modalTitlePicked: 'تأكيد الالتقاط',
            modalTitleDelivered: 'تأكيد التسليم',
            modalTitleNotDelivered: 'لم يتم التسليم',
            reason0: '--اختر--',
            reason1: 'العميل لا يجيب',
            reason2: 'العميل ألغى الطلب',
            reason3: 'لم أتمكن من فهم العميل.',
            reason4: 'العميل غير متوفر حالياً.',
            reason5: 'موقع العميل غير واضح',
            reason6: 'رقم جوال العميل غير صحيح',
            reason7: 'غيّر العميل تاريخ التسليم.',
            reason8: 'مشكلة في التطبيق',
            reason9: 'مشكلة في الإنترنت',
            reason10: 'مشكلة في السيارة',
            reason11: 'مشكلة في التغليف',
            reason12: 'لدي مشكلة خاصة',
            reason13: 'أنا مريض',
            reason14: 'أخرى'
        }
    };
    return translations[lang][key] || translations.en[key] || key;
}

function confirmOrder(awb,domain,token){
    Swal.fire({
        title: t('confirmTitle'),
        text: t('confirmText') + ' ' + awb,
        icon: "question",
        customClass: {
            icon: 'custom-swal-icon-size'
        },
        width: 300,
        showCancelButton: true,
        cancelButtonText: t('cancel'),
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: t('confirm')
    }).then((result) => {

        if (result.isConfirmed) {
            confirmOrderDone(awb,domain,token);
            /*Swal.fire({
                title: "Confirmed!",
                text: "Order confirmed.",
                icon: "success"
            });*/
        }
    });
}



function normalizePodFilePath(podFile) {
    let value = String(podFile || '').trim();
    if (!value || value === '0') {
        return '';
    }
    value = value.replace(/\\/g, '/');
    value = value.replace(/^assets\/pod\//i, '');
    value = value.replace(/\/assets\/pod\//gi, '/');
    value = value.replace(/^uploads\/pod\/[^/]+\//i, '');
    const parts = value.split('/');
    return parts[parts.length - 1] || '';
}

function getMobileApiBaseUrl(){
    if (typeof window !== 'undefined' && window.location?.origin) {
        return window.location.origin + '/mobile-api/';
    }
    const scripts = document.querySelectorAll('script[src*="script.js"]');
    if (!scripts.length) {
        return 'https://trakmile.com/mobile-api/';
    }
    const src = scripts[scripts.length - 1].src;
    return src.substring(0, src.lastIndexOf('/') + 1).replace('/js/', '/');
}

function getNotDeliveredReasons(){
    const reasons = {};
    for (let i = 0; i <= 14; i++) {
        reasons[i] = t('reason' + i);
    }
    return reasons;
}

function getOrderActionTitle(action){
    if (action === 'picked') {
        return t('modalTitlePicked');
    }
    if (action === 'delvery') {
        return t('modalTitleDelivered');
    }
    return t('modalTitleNotDelivered');
}

function postToReactNative(payload){
    if (window.ReactNativeWebView) {
        window.ReactNativeWebView.postMessage(JSON.stringify(payload));
        return true;
    }
    return false;
}

function showOrderActionError(message){
    Swal.fire({
        title: t('errorTitle'),
        text: message,
        icon: 'warning',
        confirmButtonText: t('errorOk')
    });
}

function openOrderActionModal(action){
    const cfg = window.orderActionConfig || {};
    const paymentMethod = (cfg.payment_method || '').toString();
    const payload = {
        type: 'order_action_modal',
        action: action,
        awb: cfg.awb || '',
        domain: cfg.domain || '',
        token: cfg.token || '',
        ccode: cfg.ccode || '',
        payment_method: paymentMethod,
        require_pod: paymentMethod.toLowerCase() === 'credit',
        require_barcode: true,
        lang: cfg.lang || getCurrentLang(),
        upload_pod_url: getMobileApiBaseUrl() + 'uploadPod.php',
        submit_via: 'webview_callback',
        labels: {
            title: getOrderActionTitle(action),
            awb: t('modalAwb'),
            barcode: t('modalBarcode'),
            barcode_hint: t('modalBarcodeHint'),
            pod: t('modalPod'),
            pod_hint: t('modalPodHint'),
            confirm: t('modalConfirm'),
            reason: t('reasonTitle')
        }
    };

    if (action === 'not') {
        payload.show_reason = true;
        payload.reasons = getNotDeliveredReasons();
    }

    if (postToReactNative(payload)) {
        return;
    }

    showOrderActionError('React Native WebView is required for this action.');
}

window.handleOrderActionSubmit = function(data){
    const cfg = window.orderActionConfig || {};
    const otype = data && (data.action || data.otype);
    const awb = (data && data.awb) || cfg.awb;
    const domain = (data && data.domain) || cfg.domain;
    const token = (data && data.token) || cfg.token;
    const ccode = (data && data.ccode) || cfg.ccode;
    const barcode = ((data && data.barcode) || '').toString().trim();
    const podFile = normalizePodFilePath((data && data.pod_file) || '');
    const comment = (data && (data.comment != null ? data.comment : data.reason)) || 0;
    const paymentMethod = ((data && data.payment_method) || cfg.payment_method || '').toString();
    const requirePod = paymentMethod.toLowerCase() === 'credit';

    if (!barcode) {
        showOrderActionError(t('errorBarcodeRequired'));
        return;
    }

    if (requirePod && !podFile) {
        showOrderActionError(t('errorPodRequired'));
        return;
    }

    if (otype === 'not' && (!comment || comment == 0)) {
        showOrderActionError(t('reasonError'));
        return;
    }

    delivaredDone(otype, awb, domain, token, ccode, comment, barcode, podFile);
};

function pickedOrder(awb, domain, token, ccode){
    window.orderActionConfig = Object.assign({}, window.orderActionConfig || {}, {
        awb: awb,
        domain: domain,
        token: token,
        ccode: ccode
    });
    openOrderActionModal('picked');
}


function delivared(otype, awb, domain, token, ccode){
    window.orderActionConfig = Object.assign({}, window.orderActionConfig || {}, {
        awb: awb,
        domain: domain,
        token: token,
        ccode: ccode
    });
    openOrderActionModal('delvery');
}


function delivaredDone(otype, awb, domain, token, ccode, comment, barcode, podFile){
   $.ajax({
       url:"orderAction.php",
       type:"POST",
       data:{
           awb: awb,
           domain: domain,
           token: token,
           ccode: ccode,
           otype: otype,
           comment: comment,
           barcode: barcode || '',
           pod_file: podFile || ''
       },
       success:function(data){
       if(data==1){
            const dataObj = {
             type: 'back',
             mobile: 0,
             message: 'null'
             };

              window.ReactNativeWebView.postMessage(JSON.stringify(dataObj));
         }
         else if(data==8){
            showOrderActionError('Invalid order state for picked action');
         }
         else if(data==10){
            showOrderActionError(t('errorBarcodeRequired'));
         }
         else if(data==11){
            showOrderActionError(t('errorPodRequired'));
         }
         else if(data==12){
            showOrderActionError(t('errorBarcodeMismatch'));
         }
         else if(data==14){
            showOrderActionError(t('reasonError'));
         }
         else{
            showOrderActionError(t('errorText'));
         }
       }
   })
}



function not_delivared(otype, awb, domain, token, ccode){
    window.orderActionConfig = Object.assign({}, window.orderActionConfig || {}, {
        awb: awb,
        domain: domain,
        token: token,
        ccode: ccode
    });
    openOrderActionModal('not');
}


function openOrder(awb,domain,token){
    window.ReactNativeWebView.postMessage(awb);
}


function openLocation(lat,lng){
    const locationUrl = "https://www.google.com/maps?q="+lat+","+lng;
    //alert(locationUrl);
    window.ReactNativeWebView.postMessage(locationUrl);
}


function shareNo(type, mobile, message) {
    // إنشاء كائن بيانات منظم
    const dataObj = {
        type: type,
        mobile: mobile,
        message: message
    };

    // تحويله لنص JSON وإرساله
    window.ReactNativeWebView.postMessage(JSON.stringify(dataObj));
}


function confirmOrderDone(awb,domain,token){
    //alert(awb+"-"+domain+"-"+token);
   $.ajax({
        url:"confirmOrder.php",
        type:"POST",
        data:"awb="+awb+"&domain="+domain+"&token="+token,
        success:function(data){
            location.reload();
        }
    });

}


