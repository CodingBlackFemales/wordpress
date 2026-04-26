learndash.paypal_checkout=learndash.paypal_checkout||{},((a,n,e,t)=>{t.init=()=>{const n=e.__("Your payment has been received!","learndash")+"\n\n"+e.sprintf(
// translators: %s: payment type.
e.__("Payment Type: %s","learndash"),t.payment_type)+"\n"+e.sprintf(
// translators: %s: transaction ID.
e.__("Transaction ID: %s","learndash"),t.transaction_id);a.alert(n)},t.init()})(window,document,window.wp.i18n,learndash.paypal_checkout.success_payment);