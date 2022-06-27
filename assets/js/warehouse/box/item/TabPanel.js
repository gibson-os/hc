Ext.define('GibsonOS.module.hc.warehouse.box.item.TabPanel', {
    extend: 'GibsonOS.TabPanel',
    alias: ['widget.gosModuleHcWarehouseBoxItemTabPanel'],
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
                        model: 'GibsonOS.module.hc.warehouse.model.box.item.Tag',
                        autoCompleteClassname: 'GibsonOS\\Module\\Hc\\AutoComplete\\Warehouse\\TagAutoComplete',
                    }
                }
            },{
                xtype: 'gosModuleHcWarehouseBoxItemTagGrid',
                itemId: 'tags',
                addButton: {
                    disabled: true
                },
                addFunction() {
                    const tagField = me.down('#tag');
                    let record = tagField.findRecordByDisplay(tagField.getRawValue());

                    if (!record) {
                        record = {
                            name: tagField.getRawValue()
                        };
                    }

                    me.down('gosModuleHcWarehouseBoxItemTagGrid').getStore().add(record);
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
                xtype: 'gosModuleHcWarehouseBoxItemCodeGrid',
                itemId: 'codes',
                addButton: {
                    disabled: true
                },
                addFunction() {
                    me.down('gosModuleHcWarehouseBoxItemCodeGrid').getStore().add({
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
                xtype: 'gosModuleHcWarehouseBoxItemLinkGrid',
                itemId: 'links',
                addButton: {
                    disabled: true
                },
                addFunction() {
                    me.down('gosModuleHcWarehouseBoxItemLinkGrid').getStore().add({
                        name: me.down('#linkName').getValue(),
                        url: me.down('#url').getValue()
                    });
                }
            }]
        },{
            xtype: 'gosCoreComponentFormPanel',
            title: 'Dateien',
            items: [{
                xtype: 'gosModuleHcWarehouseBoxItemFileGrid',
                itemId: 'files'
            }]
        }];

        me.callParent();

        me.down('#tag').on('change', (field) => {
            me.down('gosModuleHcWarehouseBoxItemTagGrid').down('#addButton').setDisabled(
                field.getValue().length === 0
            );
        });

        me.down('#codeType').on('change', () => {
            me.down('gosModuleHcWarehouseBoxItemCodeGrid').down('#addButton').disable();
        });
        me.down('#codeType').on('select', () => {
            me.down('gosModuleHcWarehouseBoxItemCodeGrid').down('#addButton').setDisabled(
                me.down('#code').getValue().length === 0
            );
        });
        me.down('#code').on('change', (field) => {
            me.down('gosModuleHcWarehouseBoxItemCodeGrid').down('#addButton').setDisabled(
                field.getValue().length === 0 || !me.down('#codeType').findRecordByDisplay(me.down('#codeType').getValue())
            );
        });

        me.down('#linkName').on('change', (field) => {
            me.down('gosModuleHcWarehouseBoxItemLinkGrid').down('#addButton').setDisabled(
                field.getValue().length === 0 || me.down('#url').getValue().length === 0 || !me.down('#url').isValid()
            );
        });
        me.down('#url').on('change', (field) => {
            me.down('gosModuleHcWarehouseBoxItemLinkGrid').down('#addButton').setDisabled(
                field.getValue().length === 0 || me.down('#linkName').getValue().length === 0 || !field.isValid()
            );
        });

        me.down('gosModuleHcWarehouseBoxItemFileGrid').on('render', () => {
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
                    me.down('gosModuleHcWarehouseBoxItemFileGrid').getStore().add({
                        name: file.name,
                        file: file
                    });
                });
            };
        });
    }
});