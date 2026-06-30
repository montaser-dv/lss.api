function confirmOrder(awb,domain,token){
    Swal.fire({
        title: "Are you sure?",
        text: "Have you actually received this order : "+awb,
        icon: "question",
        customClass: {
            icon: 'custom-swal-icon-size'
        },
        width: 300,
        showCancelButton: true,
        cancelButtonText: "No",
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: "Yes, confirm it!"
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
        title: "Are you sure",
        text: "Order delivered?",
        icon: "question",
        customClass: {
            icon: 'custom-swal-icon-size'
        },
        width: 300,
        showCancelButton: true,
        cancelButtonText: "No",
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: "Yes, delivered."
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
                    title: 'Error!',
                    text: 'Not all transactions were saved !',
                    icon: 'warning',
                    confirmButtonText: 'Cool'
                })
         }
       }
   })
}



function not_delivared(otype,awb,domain,token,ccode){
    Swal.fire({
        title: "Choose the reason",
        input: "select",
        inputOptions: {
            0: '--Select--',
            1: 'Customer does not answer',
            2: 'The customer cancelled the order',
            3: 'I couldnt understand the customer.',
            4: 'The customer is currently unavailable.',
            5: 'Customer location is not clear',
            6: 'Customer mobile number is incorrect',
            7: 'The customer changed the delivery date.',
            8: 'Application problem',
            9: 'Internet problem',
            10: 'car problem',
            11: 'Packaging problem',
            12: 'I have special problem',
            13: 'I am sick',
            14: 'Other'
        },
        icon: "info",
        customClass: {
            icon: 'custom-swal-icon-size'
        },
        width: 400,
        showCancelButton: true,
        cancelButtonText: "Cancel",
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: "Submit"
    }).then((result) => {

        if (result.isConfirmed) {

            if(result.value==0){
                Swal.fire({
                    title: 'Error!',
                    text: 'You did not choose the reason',
                    icon: 'warning',
                    confirmButtonText: 'Cool'
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


