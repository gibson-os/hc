Ext.define('GibsonOS.module.hc.warehouse.label.element.Form', {
    extend: 'GibsonOS.module.core.component.form.Panel',
    alias: ['widget.gosModuleHcWarehouseLabelElementForm'],
    initComponent() {
        const me = this;

        me.items = [{
            xtype: 'gosModuleCoreParameterTypeAutoComplete',
            fieldLabel: 'Typ',
            name: 'type',
            parameterObject: {
                config: {
                    model: 'GibsonOS.module.hc.warehouse.model.label.ElementType',
                    autoCompleteClassname: 'GibsonOS\\Module\\Hc\\AutoComplete\\Warehouse\\Label\\Element\\TypeAutoComplete'
                }
            }
        },{
            xtype: 'gosCoreComponentFormFieldNumberField',
            fieldLabel: 'Breite',
            name: 'width',
            step: 0.1,
            minValue: 0.01
        },{
            xtype: 'gosCoreComponentFormFieldNumberField',
            fieldLabel: 'Höhe',
            name: 'height',
            step: 0.1,
            minValue: 0.01
        },{
            xtype: 'gosCoreComponentFormFieldNumberField',
            fieldLabel: 'Links',
            name: 'left',
            step: 0.1,
            minValue: 0.01
        },{
            xtype: 'gosCoreComponentFormFieldNumberField',
            fieldLabel: 'Oben',
            name: 'top',
            step: 0.1,
            minValue: 0.01
        }];

        me.callParent();
    }
});