<h2><?=$header?></h2>

<?=form_open('auth/admin/acl_permissions/save')?>  
<table width=100% cellspacing=0>
    <tr>
        <td width=33%><b><?=$this->lang->line('access_groups')?></b></td>
        <td width=33%><b><?=$this->lang->line('access_resources')?></b></td>
        <td width=33%><b><?=$this->lang->line('access_actions')?></b></td>
    </tr>
    <tr>
        <td><div class="scrollable_tree"><ul id="groups"><?=$this->access_control_model->buildGroupSelector(($_POST['id']!=NULL))?></ul></div></td>
        <td><div class="scrollable_tree"><ul id="resources"><?=$this->access_control_model->buildResourceSelector(($_POST['id']!=NULL))?></ul></div></td>
        <td><div class="scrollable_tree"><?=$this->access_control_model->buildActionSelector()?></div></td>
    </tr>
    <tr>
        
        <td colspan=3>
            <b><?=$this->lang->line('access')?>:</b><br>
            <?=form_radio('allow','Y',$this->validation->set_radio('allow','Y')) . $this->lang->line('access_allow')?>
            <?=form_radio('allow','N',$this->validation->set_radio('allow','N')) . $this->lang->line('access_deny')?>
        </td>
    </tr>
</table>
<?=form_hidden('id',$this->validation->id)?>
<?=form_submit('submit',$this->lang->line('access_save'))?>
<?=form_close()?>