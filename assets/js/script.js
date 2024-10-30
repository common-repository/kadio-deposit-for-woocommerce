jQuery(function($){
       $('input[name=kadio_deposit]').change(function () {
           //console.log(this.value);
           if (this.value != 1 ) {
               $('.order-deposit').show();
           } else {
               $('.order-deposit').hide();
           }

       })
        // $(".wp-element-button").click(function(){
        //     $("#kadio_deposit_field").load(" #kadio_deposit_field");

        // })
        

        function verifyDeposit(){
            var data = {
                action: 'kadioDepositAJAX_callback',
                security: kadio_deposit_for_woocommerce.ajaxnonce
            };
            

            jQuery.post(
                kadio_deposit_for_woocommerce.ajaxurl, 
                data,
                function( response ) {
                    // ERROR HANDLING
                    if( !response.success ) {
                      console.log("error");
                    }
                    else
                    console.log(response.data);
                    if( response.data ){
                        $(".kadio-deposit").show();

                    }else{
                        $(".kadio-deposit").hide();
                    }
                }
        );
        }
   }); 