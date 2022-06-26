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
                itemId: 'tag',
                parameterObject: {
                    config: {
                        model: 'GibsonOS.module.hc.warehouse.model.Tag',
                        autoCompleteClassname: 'GibsonOS\\Module\\Hc\\AutoComplete\\Warehouse\\TagAutoComplete',
                    }
                }
            },{
                xtype: 'gosModuleHcWarehouseBoxTagGrid',
                addButton: {
                    disabled: true
                },
                addFunction() {
                    const tagField = me.down('#tag');
                    let record = tagField.findRecordByDisplay(tagField.getRawValue());

                    if (!record) {
                        record = new GibsonOS.module.hc.warehouse.model.Tag({
                            name: tagField.getRawValue()
                        });
                    }

                    me.down('gosModuleHcWarehouseBoxTagGrid').getStore().add(record);
                }
            }]
        },{
            xtype: 'gosCoreComponentFormPanel',
            title: 'Codes',
            items: [{
                xtype: 'gosModuleCoreParameterTypeAutoComplete',
                fieldLabel: 'Type',
                itemId: 'codeType',
                parameterObject: {
                    config: {
                        model: 'GibsonOS.module.hc.warehouse.model.CodeType',
                        autoCompleteClassname: 'GibsonOS\\Module\\Hc\\AutoComplete\\Warehouse\\CodeTypeAutoComplete'
                    }
                }
            },{
                xtype: 'gosCoreComponentFormFieldTextField',
                fieldLabel: 'Code',
                itemId: 'code'
            },{
                xtype: 'gosModuleHcWarehouseBoxCodeGrid',
                addButton: {
                    disabled: true
                },
                addFunction() {
                    me.down('gosModuleHcWarehouseBoxCodeGrid').getStore().add({
                        type: me.down('#codeType').getValue(),
                        code: me.down('#code').getValue()
                    });
                }
            }]
        },{
            xtype: 'gosCoreComponentFormPanel',
            title: 'Links',
            items: [{
                xtype: 'gosCoreComponentFormFieldTextField',
                fieldLabel: 'Name',
                itemId: 'linkName'
            },{
                xtype: 'gosCoreComponentFormFieldTextField',
                fieldLabel: 'URL',
                itemId: 'url',
                vtype: 'url'
            },{
                xtype: 'gosModuleHcWarehouseBoxLinkGrid',
                addButton: {
                    disabled: true
                },
                addFunction() {
                    me.down('gosModuleHcWarehouseBoxLinkGrid').getStore().add({
                        name: me.down('#linkName').getValue(),
                        url: me.down('#url').getValue()
                    });
                }
            }]
        },{
            xtype: 'gosCoreComponentFormPanel',
            title: 'Dateien',
            items: [{
                xtype: 'gosModuleHcWarehouseBoxFileGrid'
            }]
        },{
            xtype: 'gosCoreComponentFormPanel',
            title: 'LEDs',
            items: [{
                xtype: 'gosModuleCoreParameterTypeAutoComplete',
                fieldLabel: 'LED',
                itemId: 'led',
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
                addButton: {
                    disabled: true
                },
                addFunction() {
                    const ledField = me.down('#led');
                    me.down('gosModuleHcWarehouseBoxLedGrid').getStore().add(
                        ledField.findRecordByValue(ledField.getValue())
                    );
                }
            }]
        }];

        me.callParent();

        me.down('#tag').on('change', (field) => {
            me.down('gosModuleHcWarehouseBoxTagGrid').down('#addButton').setDisabled(
                field.getValue().length === 0
            );
        });

        me.down('#codeType').on('change', () => {
            me.down('gosModuleHcWarehouseBoxCodeGrid').down('#addButton').disable();
        });
        me.down('#codeType').on('select', () => {
            me.down('gosModuleHcWarehouseBoxCodeGrid').down('#addButton').setDisabled(
                me.down('#code').getValue().length === 0
            );
        });
        me.down('#code').on('change', (field) => {
            me.down('gosModuleHcWarehouseBoxCodeGrid').down('#addButton').setDisabled(
                field.getValue().length === 0 || !me.down('#codeType').findRecordByDisplay(me.down('#codeType').getValue())
            );
        });

        me.down('#linkName').on('change', (field) => {
            me.down('gosModuleHcWarehouseBoxLinkGrid').down('#addButton').setDisabled(
                field.getValue().length === 0 || me.down('#url').getValue().length === 0 || !me.down('#url').isValid()
            );
        });
        me.down('#url').on('change', (field) => {
            me.down('gosModuleHcWarehouseBoxLinkGrid').down('#addButton').setDisabled(
                field.getValue().length === 0 || me.down('#linkName').getValue().length === 0 || !field.isValid()
            );
        });

        me.down('gosModuleHcWarehouseBoxFileGrid').on('render', () => {
            const element = me.getEl().dom;
            const stopEvents = (event) => {
                event.stopPropagation();
                event.preventDefault();
            };
            element.ondragover = stopEvents;
            element.ondrageleave = stopEvents;
            element.ondrop = (event) => {
                stopEvents(event);

                Ext.iterate(event.dataTransfer.files, (file) => {
                    me.down('gosModuleHcWarehouseBoxFileGrid').getStore().add({
                        name: file.name,
                        file: file
                    });
                });
            };
        });

        me.down('#led').on('change', () => {
            me.down('gosModuleHcWarehouseBoxLedGrid').down('#addButton').disable();
        });
        me.down('#led').on('select', () => {
            me.down('gosModuleHcWarehouseBoxLedGrid').down('#addButton').enable();
        });
    }
});