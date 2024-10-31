<div id="multiparcels-amazing-shipping">
    <h1 id="multiparcels-amazing-shipping-title-sending">
		<?php _e( 'Sending...', 'multiparcels-shipping-for-woocommerce' ) ?>
    </h1>
    <h1 id="multiparcels-amazing-shipping-title-done" style="display: none">
		<?php _e( 'Done', 'multiparcels-shipping-for-woocommerce' ) ?>!
    </h1>
    <h1 id="multiparcels-amazing-shipping-title-numbers" style="margin-bottom: 15px;">
        <span id="multiparcels-amazing-shipping-done">-</span> / <span
                id="multiparcels-amazing-shipping-shipments">-</span>
    </h1>
    <div id="multiparcels-amazing-shipping-progress-bar">
        <div id="multiparcels-amazing-shipping-progress-bar" class="progress-bar">
            <span style="width: 0%;"></span>
            <span id="multiparcels-amazing-shipping-failed-progress-bar" style="width: 0%;"></span>
        </div>
    </div>
    <div id="multiparcels-amazing-shipping-history" style="width: 100%;max-width: 800px;margin-top: 50px;">
        <div id="multiparcels-amazing-shipping-history-content">

        </div>
    </div>
</div>

<script>
    jQuery(document).ready(function ($) {
        var data = <?php echo json_encode( $shipping );?>;
        var element = $("#multiparcels-amazing-shipping");

        element.on('history_update', function (e, html) {
            $("#multiparcels-amazing-shipping-history-content").html(html);
        });
        element.on('root_update', function (e, data) {
            var done = data.done;
            var total = data.shipments;
            var percentage = ((parseInt(data.done) / parseInt(data.shipments)) * 100).toFixed(2);
            var failed_percentage = ((parseInt(data.failed) / parseInt(data.shipments)) * 100).toFixed(2);

            $('#multiparcels-amazing-shipping-done').html(done);
            $('#multiparcels-amazing-shipping-shipments').html(total);
            $('#multiparcels-amazing-shipping-progress-bar span').css('width', percentage + '%');
            $('#multiparcels-amazing-shipping-failed-progress-bar').css('width', failed_percentage + '%');

            if (data.status === 'done') {
                $('#multiparcels-amazing-shipping-title-sending').hide();
                $('#multiparcels-amazing-shipping-title-done').show();
            }
        });

        element.trigger('root_update', data);
        element.trigger('history_update', <?php echo json_encode( $history );?>);

        var running = false;
        var interval = setInterval(function () {
            if (!running) {
                running = true;
                $.ajax({
                    type: 'POST',
                    url: ajaxurl,
                    data: {
                        'action': 'multiparcels_amazing_shipping_next',
                        'id': '<?php echo $shipping['id'];?>'
                    },
                    dataType: 'json',
                    success: function (response) {
                        running = false;
                        element.trigger('root_update', response.data.shipping);
                        element.trigger('history_update', response.data.history);

                        if (response.data.shipping.status === 'done') {
                            clearInterval(interval);
                        }
                    }
                });
            }

        }, 1000);
    });
</script>

<style>
    #multiparcels-amazing-shipping {
        width: 100%;
        background: #fff;
        padding: 15px;
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 400px;
        flex-direction: column;
    }

    .progress-bar {
        background-color: #1a1a1a;
        height: 25px;
        padding: 5px;
        width: 350px;
    }

    .progress-bar span {
        background-size: 30px 30px;
        background-image: linear-gradient(135deg, rgba(255, 255, 255, .15) 25%, transparent 25%,
        transparent 50%, rgba(255, 255, 255, .15) 50%, rgba(255, 255, 255, .15) 75%,
        transparent 75%, transparent);

        animation: animate-stripes 3s linear infinite;
        background-color: #004064;

        float: left;
    }

    #multiparcels-amazing-shipping-failed-progress-bar {
        background-color: #bd0000;
    }

    @keyframes animate-stripes {
        0% {
            background-position: 0 0;
        }
        100% {
            background-position: 60px 0;
        }
    }

    .progress-bar span {
        display: block;
        height: 100%;
        box-shadow: 0 1px 0 rgba(255, 255, 255, .5) inset;
        transition: width 1s ease-in-out;
        width: 0%;
    }

    #multiparcels-amazing-shipping-history td {
        padding: 8px;
    }
</style>