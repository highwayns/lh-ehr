<div class="table-responsive">
<table class="table table-hover">
        <tr>
                <th><?php echo xlt("Name");?></th>
                <th>&nbsp;</th>
                <th><?php echo xlt("Provider");?>#</th>
                <th><?php echo xlt("Rendering");?>#</th>
                <th><?php echo xlt("Group");?>#</th>
        </tr>
        
        <?php if(is_array($this->providers)) { 
           foreach ($this->providers as $value) {?>
            <tr>
                <td><a href="<?php echo $this->current_action; ?>action=edit&id=default&provider_id=<?php echo $value->id;?>">
                    <?php echo $value->get_name_display();?></a>
                </td>
                <td>
                    Default&nbsp;
                </td>
                <td><?php echo $value->get_provider_number_default();?>&nbsp;</td>
                <td><?php echo $value->get_rendering_provider_number_default();?>&nbsp;</td>
                <td><?php echo $value->get_group_number_default();?>&nbsp;</td>                    
            </tr>
        <?php } }
        else { ?>
            <tr class="center_display">
                <td colspan="5"><?php echo xlt("No Providers Found");?> </td>
            </tr>
        <?php }?>
    </table>
</div>