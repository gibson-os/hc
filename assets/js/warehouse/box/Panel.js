Ext.define('GibsonOS.module.hc.warehouse.box.Panel', {
    extend: 'GibsonOS.module.core.component.Panel',
    alias: ['widget.gosModuleHcWarehouseBoxPanel'],
    layout: 'border',
    enableContextMenu: true,
    enableKeyEvents: true,
    addFunction() {
        const store = this.viewItem.getStore();
        let maxTop = 0;

        store.each((box) => {
            if (box.get('left') <= 3) {
                let top = box.get('top');
                let height = box.get('height');
                maxTop = (maxTop > top + height) ? maxTop : (top + height);
            }
        });

        store.add({
            left: 0,
            top: maxTop,
            width: 3,
            height: 2,
            leds: [],
            items: []
        });
    },
    deleteFunction(records) {
        this.viewItem.getStore().remove(records);
    },
    initComponent() {
        const me = this;

        me.viewItem = new GibsonOS.module.hc.warehouse.box.View({
            region: 'center',
            moduleId: me.moduleId,
            overflowX: 'auto',
            overflowY: 'auto'
        });

        me.items = [me.viewItem, {
            xtype: 'gosCoreComponentPanel',
            itemId: 'east',
            region: 'east',
            disabled: true,
            flex: 0,
            width: 300,
            addFunction() {
            },
            deleteFunction() {
            },
            items: [{
                xtype: 'gosModuleHcWarehouseBoxTabPanel',
                flex: 0,
                moduleId: me.moduleId
            }]
        }];

        me.callParent();

        me.addAction({
            iconCls: 'icon_system system_show',
            selectionNeeded: true,
            minSelectionNeeded: 1,
            handler() {
                me.setLoading(true);

                const selectionModel = me.viewItem.getSelectionModel();
                const record = selectionModel.getSelection()[0];

                GibsonOS.Ajax.request({
                    url: baseDir + 'hc/warehouse/show',
                    params:  {
                        moduleId: me.moduleId,
                        id: record.get('id')
                    },
                    callback() {
                        me.setLoading(false);
                    }
                });
            }
        });
        me.addAction({
            iconCls: 'icon_system system_save',
            handler() {
                let boxes = [];
                let newFileIndex = 0;
                let newImageIndex = 0;

                me.setLoading(true);

                const formData = new FormData();

                me.viewItem.getStore().each((box) => {
                    let boxData = Ext.clone(box.getData());

                    Ext.iterate(boxData.items, (item) => {
                        Ext.iterate(item.files, (file) => {
                            if (file.file) {
                                formData.append('newFiles[]', file.file);
                                file.fileIndex = newFileIndex++;
                            }
                        });

                        if (item.imageFile) {
                            formData.append('newImages[]', item.imageFile);
                            item.imageIndex = newImageIndex++;
                        }

                        item.imageSource = null;
                    });

                    boxes.push(boxData);
                });

                formData.append('moduleId', me.moduleId);
                formData.append('boxes', Ext.encode(boxes));

                const xhr = new XMLHttpRequest();
                xhr.open('POST', baseDir + 'hc/warehouse/save');
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                xhr.upload.onprogress = function(uploadEvent) {
                    // if (options.progress) {
                    //     options.progress(uploadEvent, files[i]);
                    // }
                };
                xhr.onreadystatechange = function() {
                    if (xhr.readyState !== 4) {
                        return false;
                    }

                    me.setLoading(false);

                    if (xhr.status !== 200) {
                        // options.failure(null);
                        return false;
                    }

                    const response = Ext.decode(xhr.responseText);

                    if (response.failure) {
                        GibsonOS.MessageBox.show({msg: 'Datei konnte nicht hochgeladen werden!'});

                        return false;
                    }
                };

                xhr.send(formData);
            }
        });

        const east = me.down('#east');

        east.addFunction = () => {
            const boxes = me.viewItem.getSelectionModel().getSelection();

            if (boxes.length !== 1) {
                return;
            }

            const items = boxes[0].get('items');
            let item = {};
            items.push(item);
            boxes[0].set('items', items);
            me.addItemTab(boxes[0], item);
            me.viewItem.refresh();
        };
        east.deleteFunction = () => {
            const boxes = me.viewItem.getSelectionModel().getSelection();

            if (boxes.length !== 1) {
                return;
            }

            const items = boxes[0].get('items');
            const tabPanel = east.down('tabpanel');
            const activeTab = tabPanel.getActiveTab();
            const index = tabPanel.items.indexOf(activeTab);

            tabPanel.remove(activeTab);
            items.splice(index-1, 1);
        };
        east.down('tabpanel').on('tabchange', (tabPanel, newTab) => {
            east.down('#deleteButton').setDisabled(tabPanel.items.indexOf(newTab) < 1);
        });

        me.viewItem.on('render', function() {
            me.viewItem.dragZone = Ext.create('Ext.dd.DragZone', me.viewItem.getEl(), {
                getDragData: function(event) {
                    let sourceElement = event.getTarget(me.viewItem.itemSelector, 10);

                    if (sourceElement) {
                        let clone = sourceElement.cloneNode(true);
                        clone.style = 'position: relative;';

                        return me.viewItem.dragData = {
                            sourceEl: sourceElement,
                            repairXY: Ext.fly(sourceElement).getXY(),
                            ddel: clone,
                            record: me.viewItem.getRecord(sourceElement)
                        };
                    }
                },
                getRepairXY: function() {
                    return me.viewItem.dragData.repairXY;
                }
            });
            me.viewItem.dropZone = GibsonOS.dropZones.add(me.viewItem.getEl(), {
                getTargetFromEvent: function(event) {
                    return event.getTarget('#' + me.viewItem.getId());
                },
                onNodeOver: function(target, dd, event, data) {
                    if (data.record instanceof GibsonOS.module.hc.warehouse.model.Box) {
                        return Ext.dd.DropZone.prototype.dropAllowed;
                    }

                    return Ext.dd.DropZone.prototype.dropNotAllowed;
                },
                onNodeDrop: function(target, dd, event, data) {
                    let element = me.viewItem.getEl().dom;
                    let boundingClientRect = element.getBoundingClientRect();
                    let elementLeft = (dd.lastPageX + element.scrollLeft) - boundingClientRect.x;
                    let elementTop = (dd.lastPageY + element.scrollTop) - boundingClientRect.y;

                    data.record.set('left', Math.floor((elementLeft - me.viewItem.offsetLeft - 17) / me.viewItem.gridSize));
                    data.record.set('top', Math.floor((elementTop - me.viewItem.offsetTop - 25) / me.viewItem.gridSize));

                    me.viewItem.getStore().each((key) => key.commit());
                }
            });
        });
        me.viewItem.on('itemkeydown', function(view, record, item, index, event) {
            let moveRecords = function(left, top) {
                Ext.iterate(me.viewItem.getSelectionModel().getSelection(), function(record) {
                    record.set('left', record.get('left') + left);
                    record.set('top', record.get('top') + top);
                });

                me.viewItem.getStore().each((key) => key.commit());
            };

            switch (event.getKey()) {
                case Ext.EventObject.S:
                    moveRecords(0, 1);
                    break;
                case Ext.EventObject.W:
                    moveRecords(0, -1);
                    break;
                case Ext.EventObject.A:
                    moveRecords(-1, 0);
                    break;
                case Ext.EventObject.D:
                    moveRecords(1, 0);
                    break;
            }
        });
        const ledStoreChangeFunction = (store) => {
            const boxes = me.viewItem.getSelectionModel().getSelection();

            if (boxes.length !== 1) {
                return;
            }

            let records = [];

            store.each((record) => {
                records.push(record.getData());
            });

            boxes[0].set('leds', records);
        };
        me.viewItem.on('selectionchange', (view, records) => {
            const panel = me.down('panel');
            const tabPanel = panel.down('tabpanel');
            tabPanel.removeAll();
            const defaultForm = tabPanel.add(tabPanel.getDefaultTab());
            tabPanel.setActiveTab(defaultForm);

            if (records.length !== 1) {
                panel.disable();

                return;
            }

            const record = records[0];
            defaultForm.loadRecord(record);
            defaultForm.getForm().getFields().each((field) => {
                field.on('change', (field, value) => {
                    const boxes = me.viewItem.getSelectionModel().getSelection();

                    if (boxes.length !== 1) {
                        return;
                    }

                    boxes[0].set(field.name, value);
                });
            });
            defaultForm.down('#uuid').update({
                boxId: record.get('id')
            });

            const ledStore = defaultForm.down('gosModuleHcWarehouseBoxLedGrid').getStore();

            ledStore.on('add', ledStoreChangeFunction);
            ledStore.on('remove', ledStoreChangeFunction);

            Ext.iterate(record.get('leds'), (led) => {
                ledStore.add(led);
            });

            Ext.iterate(record.get('items'), (item) => {
                me.addItemTab(record, item);
            });

            panel.enable();
        });
    },
    addItemTab(record, item) {
        const me = this;
        const tabPanel = me.down('panel').down('tabpanel');
        const itemPanel = tabPanel.addItemTab(
            new GibsonOS.module.hc.warehouse.model.box.Item(item)
        );

        itemPanel.down('form').loadRecord(new GibsonOS.module.hc.warehouse.model.box.Item(item));
        itemPanel.down('form').getForm().getFields().each((field) => {
            field.on('change', (field, value) => {
                const boxes = me.viewItem.getSelectionModel().getSelection();

                if (boxes.length !== 1) {
                    return;
                }

                item[field.name] = value;
            });
        });

        const image = itemPanel.down('#image');

        image.itemId = item.id;
        image.update({
            itemId: item.id,
            name: record.get('name'),
            image: item.image,
            src: item.imageSource ?? ''
        });
        image.on('imageUploaded', (field, source, file) => {
            item.imageSource = source;
            item.imageFile = file;
            me.viewItem.refresh();
        });

        itemPanel.down('tabpanel').items.each((itemTabPanel) => {
            const itemTabPanelGrid = itemTabPanel.down('grid');

            Ext.iterate(item[itemTabPanelGrid.itemId], (recordItem) => {
                if (itemTabPanelGrid.itemId === 'tags') {
                    recordItem = recordItem.tag;
                }

                const itemTabPanelGridStore = itemTabPanelGrid.getStore();
                itemTabPanelGridStore.add(recordItem);
                itemTabPanelGridStore.on('change', (store) => storeChangeFunction(store, item));
                itemTabPanelGridStore.on('remove', (store) => storeChangeFunction(store, item));
            });
        });
        itemPanel.down('gosModuleHcWarehouseBoxItemTabPanel').items.each((formPanel) => {
            const grid = formPanel.down('grid');
            const storeChangeFunction = (store) => {
                const keys = me.viewItem.getSelectionModel().getSelection();

                if (keys.length !== 1) {
                    return;
                }

                let records = [];

                store.each((record) => {
                    record = record.getData();

                    if (grid.itemId === 'tags') {
                        record = {tag: record};
                    }

                    records.push(record);
                });

                item[grid.itemId] = records;
            };

            grid.getStore().on('add', storeChangeFunction);
            grid.getStore().on('remove', storeChangeFunction);
        });
    }
});