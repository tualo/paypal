delimiter //

CREATE OR REPLACE PROCEDURE `processPaypalEvent`( IN  request JSON )
BEGIN 
    SET @type = JSON_Value( request, "$.type" );

    IF @type = 'checkout.session.completed' THEN
        SET @id = JSON_Value( request, "$.data.object.id" );
        SET @payment_intent = JSON_Value( request, "$.data.object.payment_intent" );
        SET @status = JSON_Value( request, "$.data.object.status" );

        IF @status = 'complete' THEN
            SET @amount = JSON_Value( request, "$.data.object.amount_total" );
            SET @rn = (select id from `blg_hdr_rechnung` where `braintree_id` = @id);
            SET @new_id = (select ifnull(max(id),0)+1 from blg_pay_rechnung);
            INSERT INTO `blg_pay_rechnung` (
                id,
                datum,
                belegnummer,
                art,
                betrag,
                stripe_payment_intent
            ) values (
                @new_id,
                curdate(),
                @rn,
                'braintree',
                @amount / 100,
                @payment_intent
            );
        END IF;
        
    END IF;

END //


CREATE TRIGGER IF NOT EXISTS braintree_webhook_processBraintreeEvent
AFTER INSERT  
ON braintree_webhook FOR EACH ROW
BEGIN
    call processBraintreeEvent(new.eventdata);
END //


