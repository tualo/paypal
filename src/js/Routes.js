Ext.define('Tualo.routes.Paypal',{
    url: 'paypal',
    handler: {
        action: function(token){
            console.log('onAnyRoute',token);
            alert('paypal','ok');
        },
        before: function (action) {
            console.log('onBeforeToken',action);
            console.log(new Date());
            action.resume();
        }
    }
});