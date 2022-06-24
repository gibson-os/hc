Ext.define('GibsonOS.module.hc.warehouse.box.TabPanel', {
    extend: 'GibsonOS.TabPanel',
    alias: ['widget.gosModuleHcWarehouseBoxTabPanel'],
    itemId: 'hcModuleTabPanel',
    border: true,
    initComponent() {
        const me = this;

        me.items = [{
            xtype: 'gosCoreComponentFormPanel',
            title: 'Tags',
            items: [{
                xtype: 'gosModuleCoreParameterTypeAutoComplete',
                fieldLabel: 'Tag',
                name: 'ledId',
                parameterObject: {
                    config: {
                        model: 'GibsonOS.module.hc.warehouse.model.Tag',
                        autoCompleteClassname: 'GibsonOS\\Module\\Hc\\AutoComplete\\Warehouse\\TagAutoComplete',
                    }
                }
            },{
                xtype: 'gosModuleHcWarehouseBoxTagGrid',
            }]
        },{
            xtype: 'gosCoreComponentFormPanel',
            title: 'Codes',
            items: [{
                xtype: 'gosModuleHcWarehouseBoxCodeGrid',
            }]
        },{
            xtype: 'gosCoreComponentFormPanel',
            title: 'Links',
            items: [{
                xtype: 'gosCoreComponentFormFieldTextField',
                fieldLabel: 'Name',
                name: 'name'
            },{
                xtype: 'gosCoreComponentFormFieldTextField',
                fieldLabel: 'URL',
                name: 'url'
            },{
                xtype: 'gosModuleHcWarehouseBoxLinkGrid',
            }]
        },{
            xtype: 'gosCoreComponentFormPanel',
            title: 'Dateien',
            items: [{
                xtype: 'gosModuleHcWarehouseBoxFileGrid',
            }]
        },{
            xtype: 'gosCoreComponentFormPanel',
            title: 'LEDs',
            items: [{
                xtype: 'gosModuleCoreParameterTypeAutoComplete',
                fieldLabel: 'LED',
                name: 'ledId',
                displayField: 'number',
                parameterObject: {
                    config: {
                        model: 'GibsonOS.module.hc.neopixel.model.Led',
                        autoCompleteClassname: 'GibsonOS\\Module\\Hc\\AutoComplete\\Neopixel\\LedAutoComplete',
                        parameters: {
                            moduleId: me.moduleId
                        }
                    }
                }
            },{
                xtype: 'gosModuleHcWarehouseBoxLedGrid',
            }]
        }];

        me.callParent();
    }
});