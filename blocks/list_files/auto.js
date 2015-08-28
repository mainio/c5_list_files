var ListFilesBlockHelper = {

    fileRowIndex: 0,

    init: function() {
        $('select#list_type').change(function() {
            $('#file-selectors .file-selector').css('display', 'none');
            $('#file-selectors .file-selector-'+$(this).val()).css('display', 'block');
            $('#file-selectors select#fsID').val(0);
            $('#ccm-files-container').html('');
        });
        $('#file-selectors select#fsID').change(function() {
            $('#ccm-files-container').html('');
            if ($(this).val() > 0) {
                ListFilesBlockHelper.addFileSet($(this).val());
            }
        });
        $("#file-adder").click(function(ev) {
            ev.preventDefault();
            // Add filters for the file manager
            var filters = '';
            $(this).parent().find('.filters input.ccm-file-manager-filter').each(function() {
                filters += '&'+$(this).attr('name')+'='+$(this).val();
            });
            // TODO: Is it possible to pass filters to the 5.7 file manager?
            ConcreteFileManager.launchDialog(function(data) {
                ListFilesBlockHelper.addFile(data.fID);
            });
        });

        // Init selected files
        var selected = $('#ccm-files-container input[name="selectedFileIDs"]');
        if (selected.val().length > 0) {
            this.addFiles(selected.val().split(','));
        }
        selected.remove();

        $('#ccm-files-container').sortable({
            axis: 'y',
            handle: '.mover',
            items: 'div.file-row'
        });
    },

    addFile: function(fID) {
        // TODO: This same could be done already in our own request to avoid two
        //       calls to the server.
        ConcreteFileManager.getFileDetails(fID, function(resp) {
            if (resp.files && resp.files.length > 0) {
                var file = resp.files[0];
                ListFilesBlockHelper.addFileFromData(file);
            }
        });
    },

    addFiles: function(fileIDs) {
        var qstring = '';
        for (var i in fileIDs) {
            if (i > 0) qstring += '&';
            qstring += 'fID[]='+fileIDs[i];
        }
        $.getJSON(CCM_DISPATCHER_FILENAME + '/ccm/system/file/get_json?' + qstring, function(resp) {
            if (resp.files && resp.files.length > 0) {
                var extraData = {};
                var num = 0;
                for (var i in resp.files) {
                    $.getJSON(CCM_LIST_FILES_FILE_DETAILS_URL + '/' + resp.files[i].fID + '/' + CCM_LIST_FILES_BID, function(fresp) {
                        if (fresp !== null) {
                            extraData[fresp.fID] = fresp;
                        }
                        num++;
                        if (num == resp.files.length) {
                            // Make sure we add the files in the correct order
                            // after we've fetched the extra data for this block.
                            for (var i in resp.files) {
                                var fID = resp.files[i].fID;
                                if (extraData[fID]) {
                                    if (extraData[fID].title) {
                                        resp.files[i].title = extraData[fID].title;
                                    }
                                }
                                ListFilesBlockHelper.addFileFromDataFinal(resp.files[i]);
                            }
                        }
                    });
                }
            }
        });
    },

    addFileSet: function(fsID) {
        $.getJSON(CCM_LIST_FILES_FILESET_DETAILS_URL+'/'+fsID, function(resp) {
            if (resp != null && resp.fsID !== undefined) {
                ListFilesBlockHelper.addFiles(resp.files);
            }
        });
    },

    addFileFromData: function(file) {
        $.getJSON(CCM_LIST_FILES_FILE_DETAILS_URL + '/' + file.fID + '/' + CCM_LIST_FILES_BID, function(resp) {
            if (resp !== null) {
                if (resp.title) {
                    file.title = resp.title;
                }
            }
            ListFilesBlockHelper.addFileFromDataFinal(file);
        });
    },

    addFileFromDataFinal: function(file) {
        this.fileRowIndex++;
        var fileSetMode = $('#file-selectors select#fsID').val() > 0;
        var rowID = 'file-row-'+(""+this.fileRowIndex);
        var row = $('<div id="'+rowID+'" class="file-row clearfix"></div>');
        row.append('<div class="mover"><span class="fa fa-arrows-v"></span></div>');
        if (!fileSetMode) {
            row.append('<div class="remover"><span class="fa fa-trash"></span></div>');
        }
        row.append('<div class="editor"><span class="fa fa-edit"></div>');
        row.append('<input type="hidden" name="fID[]" value="' + file.fID + '" />');
        row.append('<div class="file-preview">'+file.resultsThumbnailImg+'</div>');
        row.append('<div class="file-name">'+file.title+'</div>');
        $('#ccm-files-container').append(row);

        // Init remover
        row.find('div.remover').click(function() {
            row.remove();
        });
        
        row.find('div.editor').click(function(ev) {
            ev.preventDefault();
            var editRow = $(this).parent();
            var openDialog = $.fn.dialog.open({
                title: ccm_t('file-properties'),
                width: 700,
                height: 400,
                href: CCM_LIST_FILES_PROPERTIES_URL + '/' + file.fID + '/' + CCM_LIST_FILES_BID,
                appendButtons: true,
                onOpen: function() {
                    var btns = $('.dialog-buttons.attributes-actions');
                    btns.find('a.cancel').click(function(ev) {
                        ev.preventDefault();
                        jQuery.fn.dialog.closeTop();
                    });
                    btns.find('input[type="submit"]').click(function() {
                        var form = $('form.file-attributes-form');
                        tinyMCE.triggerSave();
                        $.post(form.attr('action'), form.serialize(), function(ret) {
                            if (ret.success) {
                                editRow.find('.file-name').html($.trim(form.find('input#fvTitle').val()));
                                jQuery.fn.dialog.closeTop();
                            } else {
                                console.log('Error sending the form!');
                            }
                        }, 'json');
                    });
                }
            });
        });

        // Sortable needs to be called after ajax updates
        $('#ccm-files-container').sortable("refresh");
    },
    
    validate: function() {
        if ($('#ccm-files-container input[name="fID[]"]').length < 1) {
            ccm_addError(ccm_t('add-files'));
            return false;
        }
        return true;
    }

};

// TODO: Is there some other function for this in the new 5.7 API?
ccmValidateBlockForm = function() {return ListFilesBlockHelper.validate();};
