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
            xtype: 'gosCoreComponentFormFieldContainer',
            fieldLabel: 'Vorlage',
            items: [{
                xtype: 'gosModuleCoreParameterTypeAutoComplete',
                name: 'templateId',
                margins: '0 5 0 0',
                parameterObject: {
                    config: {
                        model: 'GibsonOS.module.hc.warehouse.model.label.Template',
                        autoCompleteClassname: 'GibsonOS\\Module\\Hc\\AutoComplete\\Warehouse\\Label\\TemplateAutoComplete'
                    }
                },
            }, {
                xtype: 'gosButton',
                flex: 0,
                text: '...',
                handler() {
                    new GibsonOS.module.hc.warehouse.label.template.App();
                }
            }]
        }];

        me.buttons = [{
            xtype: 'gosButton',
            text: 'Speichern',
            handler() {
                me.getForm().submit({
                    xtype: 'gosFormActionAction',
                    url: baseDir + 'hc/warehouseLabel',
                    method: 'POST'
                });
            }
        }];

        me.callParent();
    }
});