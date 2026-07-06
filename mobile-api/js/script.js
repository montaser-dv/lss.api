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



function delivared(otype,awb,domain,token,ccode){
  var comment=0;
    Swal.fire({
        title: t('deliverTitle'),
        text: t('deliverText'),
        icon: "question",
        customClass: {
            icon: 'custom-swal-icon-size'
        },
        width: 300,
        showCancelButton: true,
        cancelButtonText: t('cancel'),
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: t('deliverConfirm')
    }).then((result) => {

        if (result.isConfirmed) {
            delivaredDone(otype,awb,domain,token,ccode,comment);
            /*Swal.fire({
                title: "Confirmed!",
                text: "Order confirmed.",
                icon: "success"
            });*/
        }
    });
}


function delivaredDone(otype,awb,domain,token,ccode,comment){
   $.ajax({
       url:"orderAction.php",
       type:"POST",
       data:"awb="+awb+"&domain="+domain+"&token="+token+"&ccode="+ccode+"&otype="+otype+"&comment="+comment,
       success:function(data){
       if(data==1){
             const dataObj = {
             type: 'back',
             mobile: 0,
             message: 'null'
             };

    // تحويله لنص JSON وإرساله
              window.ReactNativeWebView.postMessage(JSON.stringify(dataObj));
         }
         else{
            Swal.fire({
                    title: t('errorTitle'),
                    text: t('errorText'),
                    icon: 'warning',
                    confirmButtonText: t('errorOk')
                })
         }
       }
   })
}



function not_delivared(otype,awb,domain,token,ccode){
    Swal.fire({
        title: t('reasonTitle'),
        input: "select",
        inputOptions: {
            0: t('reason0'),
            1: t('reason1'),
            2: t('reason2'),
            3: t('reason3'),
            4: t('reason4'),
            5: t('reason5'),
            6: t('reason6'),
            7: t('reason7'),
            8: t('reason8'),
            9: t('reason9'),
            10: t('reason10'),
            11: t('reason11'),
            12: t('reason12'),
            13: t('reason13'),
            14: t('reason14')
        },
        icon: "info",
        customClass: {
            icon: 'custom-swal-icon-size'
        },
        width: 400,
        showCancelButton: true,
        cancelButtonText: t('reasonCancel'),
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: t('reasonSubmit')
    }).then((result) => {

        if (result.isConfirmed) {

            if(result.value==0){
                Swal.fire({
                    title: t('errorTitle'),
                    text: t('reasonError'),
                    icon: 'warning',
                    confirmButtonText: t('errorOk')
                })
            }else{
                delivaredDone(otype,awb,domain,token,ccode,result.value);
            }
            //
            //confirmOrderDone(awb,domain,token);

        }
    });
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


