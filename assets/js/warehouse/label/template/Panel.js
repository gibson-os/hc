Ext.define('GibsonOS.module.hc.warehouse.label.template.Panel', {
    extend: 'GibsonOS.module.core.component.Panel',
    alias: ['widget.gosModuleHcWarehouseLabelTemplatePanel'],
    layout: 'border',
    addFunction() {
        const me = this;

        me.down('grid').getStore().add({
            pageWidth: 210,
            pageHeight: 297,
            rows: 1,
            columns: 1,
            marginTop: 1,
            marginLeft: 1,
            itemWidth: 10,
            itemHeight: 10,
            itemMarginRight: 1,
            itemMarginBottom: 1
        });
    },
    deleteFunction(records) {
        const me = this;
        let templates = [];

        Ext.iterate(records, (template) => {
            templates.push({id: template.get('id')});
        });

        let title = 'Vorlage löschen';
        let msg = 'Möchten Sie die Vorlage "' + records[0].get('name') + '" wirklich löschen?';

        if (templates.length > 1) {
            title = 'Vorlagen löschen';
            msg = 'Möchten Sie ' + templates.length + ' Vorlagen wirklich löschen?';
        }

        GibsonOS.MessageBox.show({
            title: title,
            msg: msg,
            type: GibsonOS.MessageBox.type.QUESTION,
            buttons: [{
                text: 'Ja',
                handler: function() {
                    GibsonOS.Ajax.request({
                        url: baseDir + 'hc/warehouseLabel/deleteTemplates',
                        params:  {
                            templates: Ext.encode(templates)
                        },
                        success() {
                            me.viewItem.getStore().load();
                        }
                    });
                }
            },{
                text: 'Nein'
            }]
        });
    },
    initComponent() {
        const me = this;
        const templateGrid = new GibsonOS.module.hc.warehouse.label.template.Grid({
            region: 'west',
            flex: 0,
            split: true,
            width: 150
        });

        me.viewItem = templateGrid;

        me.items = [templateGrid, {
            enableToolbar: false,
            region: 'center',
            itemId: 'center',
            layout: 'border',
            disabled: true,
            items: [{
                region: 'north',
                itemId: 'preview',
                flex: 0,
                split: true,
                height: 150,
                enableToolbar: false,
                cls: 'coloredPanel',
                overflowX: 'auto',
                overflowY: 'auto',
                data: {
                    rows: 1,
                    columns: 1,
                    marginTop: 1,
                    marginLeft: 1,
                    itemWidth: 10,
                    itemHeight: 10,
                    itemMarginRight: 1,
                    itemMarginBottom: 1
                },
                tpl: new Ext.XTemplate(
                    '<div ',
                        'class="hcWarehouseLabelTemplate" ',
                        'style="padding-top: {marginTop}mm; padding-left: {marginLeft}mm;"',
                    '>',
                        '<div class="hcWarehouseLabelTemplateRow" style="margin-bottom: {itemMarginBottom}mm;">',
                            '<div ',
                                'class="hcWarehouseLabelTemplateLabel" ',
                                'style="margin-right: {itemMarginRight}mm; width: {itemWidth}mm; height: {itemHeight}mm;"',
                            '></div>',
                            '<tpl if="columns &gt; 1">',
                                '<div ',
                                    'class="hcWarehouseLabelTemplateNextColumnLabel" ',
                                    'style="height: {itemHeight}mm;"',
                                '>+{[values.columns-1]}</div>',
                            '</tpl>',
                        '</div>',
                        '<div class="hcWarehouseLabelTemplateRow">',
                            '<tpl if="rows &gt; 1">',
                                '<div ',
                                    'class="hcWarehouseLabelTemplateNextRowLabel" ',
                                    'style="margin-right: {itemMarginRight}mm; width: {itemWidth}mm;"',
                                '>+{[values.rows-1]}</div>',
                            '</tpl>',
                            '<tpl if="rows &gt; 1 && columns &gt; 1">',
                                '<div class="hcWarehouseLabelTemplateNextRowAndColumnLabel"></div>',
                            '</tpl>',
                        '</div>',
                    '</div>'
                )
            },{
                xtype: 'gosModuleHcWarehouseLabelTemplateForm',
                region: 'center'
            }]
        }];

        me.callParent();

        me.addAction({
            iconCls: 'icon_system system_save',
            selectionNeeded: 1,
            minSelectionNeeded: 1,
            maxSelectionAllowed: 1,
            handler() {
                const records = templateGrid.getSelectionModel().getSelection();

                if (records.length !== 1) {
                    return;
                }

                me.down('form').submit({
                    xtype: 'gosFormActionAction',
                    url: baseDir + 'hc/warehouseLabel/saveTemplate',
                    success() {
                        me.viewItem.getStore().load();
                    }
                });
            }
        });

        templateGrid.on('selectionchange', (view, records) => {
            const center = me.down('#center');

            if (records.length !== 1) {
                center.disable();

                return;
            }

            const data = records[0].getData();

            center.down('form').getForm().setValues(data);
            center.down('#preview').update(data);
            center.enable();
        });
        me.down('gosModuleHcWarehouseLabelTemplateForm').getForm().getFields().each((field) => {
            field.on('change', (field, value) => {
                const templates = templateGrid.getSelectionModel().getSelection();

                if (templates.length !== 1) {
                    return;
                }

                templates[0].set(field.name, value);
                me.down('#preview').update(templates[0].getData());
            });
        });
    }
});