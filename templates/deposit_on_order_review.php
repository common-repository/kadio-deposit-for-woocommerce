<?php

if ($deposit) :;
    ?>

    <tr class="order-deposit" style="display: none">

        <th><?php echo $deposit_title; ?></th>

        <td><?php echo wp_kses(wc_price($deposit_to_pay), ['span' => []]); ?></td>

    </tr>

<?php

endif;