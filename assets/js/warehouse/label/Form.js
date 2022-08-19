Ext.define('GibsonOS.module.hc.warehouse.label.Form', {
    extend: 'GibsonOS.module.core.component.form.Panel',
    alias: ['widget.gosModuleHcWarehouseLabelForm'],
    requiredPermission: {
        permission: GibsonOS.Permission.WRITE
    },
    initComponent() {
        const me = this;

        me.items = [{
            xtype: 'gosCoreComponentFormFieldTextField',
            name: 'name',
            fieldLabel: 'Name',
        },{
            xtype: 'gosModuleCoreParameterTypeAutoComplete',
            fieldLabel: 'Vorlage',
            name: 'templateId',
            parameterObject: {
                config: {
                    model: 'GibsonOS.module.hc.warehouse.model.label.Template',
                    autoCompleteClassname: 'GibsonOS\\Module\\Hc\\AutoComplete\\Warehouse\\Label\\TemplateAutoComplete'
                }
            },
        }];

        me.buttons = [{
            xtype: 'gosButton',
            text: 'Speichern',
            handler() {
                me.getForm().submit({
                    xtype: 'gosFormActionAction',
                    url: baseDir + 'hc/warehouseLabel/save',
                });
            }
        }];

        me.callParent();
    }
});