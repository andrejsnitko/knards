'use strict';

$(function() {

    $.nette.init();
    $.ajaxSetup({ cache: false });
    $('.hamburger div').click(function() {
        $('.mobile-nav').toggleClass('menu-expand');
        $('nav:first').toggleClass('menu-top');
    });

    //---------- LOGIN ----------//
    if($('.login-wrapper').length > 0) {
        
        
        $('#btn-switch-login').on('click', function() {
            $('.div-login').toggleClass('div-hide');
            $('.div-signin').toggleClass('div-hide');
        });
        
        $('#btn-switch-signin').on('click', function() {
            $('.div-signin').toggleClass('div-hide');
            $('.div-login').toggleClass('div-hide');
        });

    }

    //---------- HOMEPAGE ----------//
    if($('.homepage-wrapper').length > 0) {
        
    }

    //---------- NEW CARD ----------//
    if($('.new-card-wrapper').length > 0) {


        $('#btn-save').on('click', function() {
            if($('.selected').length > 0) {
                var tags = '';
                $('.selected').each(function() {
                    tags += $(this).attr('name') + ',';
                });
                $('#frm-saveCardForm-tags').val(tags.substring(0, tags.length - 1).trim());
            } else {
                alert('A card must have at least one tag assigned to it.');
                return false;
            }
        
            if($('.card').html().length > 0) {
                $('#frm-saveCardForm-text').val($('.card').html().trim().encryptSpecialChars());
            } else {
                alert('Cannot save an empty card.');
                return false;
            }
        
            if($('#btn-private').attr('class') == 'locked') 
                $('#frm-saveCardForm-private').val('true');
            else $('#frm-saveCardForm-private').val('false');
        
            $('#frm-saveCardForm').submit();
        });


    }

    //---------- EDIT CARD ----------//
    if($('.edit-card-wrapper').length > 0) {
        

        $('#btn-save').on('click', function() {
            $('#frm-editCardForm-id').val($('.card').attr('id'));
        
            if($('.selected').length > 0) {
                var tags = '';
                $('.selected').each(function() {
                    tags += $(this).attr('name') + ',';
                });
                $('#frm-editCardForm-tags').val(tags.substring(0, tags.length - 1).trim());
            } else {
                alert('A card must have at least one tag assigned to it.');
                return false;
            }

            if($('.card').html().length > 0) {
                $('#frm-editCardForm-text').val($('.card').html().trim().encryptSpecialChars());
            } else {
                alert('Cannot save an empty card.');
                return false;
            }
        
            if($('#btn-private').attr('class') == 'locked') 
                $('#frm-editCardForm-private').val('true');
            else $('#frm-editCardForm-private').val('false');

            $('#frm-editCardForm-toDelete').val('');
        
            $('#frm-editCardForm').submit();
        });

        $('#btn-card-delete').on('click', function() {
            $('#frm-editCardForm-id').val($('.card').attr('id'));
            $('#frm-editCardForm-tags').val('1');
            $('#frm-editCardForm-text').val('1');
            $('#frm-editCardForm-private').val('1');
            $('#frm-editCardForm-toDelete').val('1');
            $('#frm-editCardForm').submit();
        });


    }

    //---------- NEW CARD/EDIT CARD ----------//
    if($('.new-card-wrapper').length > 0 || $('.edit-card-wrapper').length > 0) {


        // Prevent creating divs on Enter key press
        $('.card').keydown(function(e) {
            if (e.keyCode === 13) {
              document.execCommand('insertHTML', false, '<br><br>');
              return false;
            }
        });


        // Decrypt special chars
        $('h4.panel-title a').each(function() {
            $(this).html($(this).html().decryptSpecialChars());
        });

        $('.card').html($('.card').text().decryptSpecialChars());


        // Process copy paste
        $('.card').on('paste', function(event) {
            event.preventDefault();
            var pasteData = event.originalEvent.clipboardData.getData('text/html');
            var data = pasteData.encryptSpecialChars();
            var data = data.decryptSpecialChars();
            $('.card').append(data);
        });


        // Selection button action
        // Wraps selected text into <span name="q + index" class="question">
        $('#btn-selection').on('click', function() {
            var selection = window.getSelection();
            var range = selection.getRangeAt(0);
            if(selection == '')
                return false;
            replaceSelectedText(String(selection).encryptSpecialChars().decryptSpecialChars());

            var el = document.createElement('span');
            el.setAttribute('class', 'question');

            var id = 0;
            if($('.card .question').length > 0)
                $('.card .question').each(function(index) {
                    if($('[name="q' + index + '"]').length == 0) $(this).attr('name', 'q' + index);
                    id++;
                });
            el.setAttribute('name', 'q' + id);
        
            range.surroundContents(el);
            selection.removeAllRanges();
        
            if($('[name="q' + id + '"]').has('span') != 0) {
                $('[name="q' + id + '"]').html($('[name="q' + id + '"]').text());
            }
        });


        // Undo button action
        // Removes <span> with highest "id"
        $('#btn-undo').on('click', function() {
            var span = $('.card').children('[name="q' + Number($('.card').children('span').length - 1) + '"]');
            var unwrap = $('.card').children('[name="q' + Number($('.card').children('span').length - 1) + '"]').text().encryptSpecialChars().decryptSpecialChars();
        
            span.before(unwrap);
            span.remove();
        });


        // Private button action
        $('#btn-private').on('click', function() {
            if($(this).children('i').attr('class').indexOf('unlock') != -1) {
                $(this).children('i').attr('class', 'fa fa-lock');
                $(this).attr('class', 'locked');
            } else {
                $(this).children('i').attr('class', 'fa fa-unlock');
                $(this).removeAttr('class');
            }
        });


        $('.tag[name]').on('click', function() {
            if($(this).attr('class') == 'tag deselected') $(this).attr('class', 'tag selected');
            else $(this).attr('class', 'tag deselected');
        });

    }

    //---------- LIST CARDS ----------//
    if($('.list-cards-wrapper').length > 0) {


        window.sorted = 'newest-first';

        // Decrypt special chars
        $('h4.panel-title a').each(function() {
            $(this).html($(this).html().decryptSpecialChars());
        });

        $('.card').each(function() {
            var data = $(this).children('div:first').prop('outerHTML');
            var content = '<div>' + $(this).children('div:last').text().decryptSpecialChars() + '</div>';
            $(this).html(data + content);
        });


        // Init datetimepicker plugins
        $('#datetimepicker1').datetimepicker({
            format: 'DD/MM/YYYY'
        });      
        $('#datetimepicker2').datetimepicker({
            format: 'DD/MM/YYYY'
        });

        var count = 0;
        $('.card-link').each(function() {
            if($(this).css('display') != 'none') count++;
        });

        $('.actions-desktop').children('div').children('div:first').text('Cards total: ' + count);


        // Datetimepickers' values changes
        $('#datetimepicker1').on('dp.change', function() {
            $('.card .date').each(function() {
                if($(this).attr('class').substring(5) < $('#datetimepicker1 input').val())
                    $(this).parent().parent().css('display', 'none');
                else 
                    $(this).parent().parent().css('display', 'block');
            });

            var count = 0;
            $('.card-link').each(function() {
                if($(this).css('display') != 'none') count++;
            });

            $('.actions-desktop').children('div').children('div:first').text('Cards total: ' + count);
        });
        
        $('#datetimepicker2').on('dp.change', function() {
            $('.card .date').each(function() {
                if($(this).attr('class').substring(5) > $('#datetimepicker2 input').val())
                    $(this).parent().parent().css('display', 'none');
                else 
                    $(this).parent().parent().css('display', 'block');
            });

            var count = 0;
            $('.card-link').each(function() {
                if($(this).css('display') != 'none') count++;
            });

            $('.actions-desktop').children('div').children('div:first').text('Cards total: ' + count);
        });


        $('.tag[name]').on('click', function() {
            if($(this).attr('class') == 'tag deselected') $(this).attr('class', 'tag selected');
            else $(this).attr('class', 'tag deselected');

            var tag_name = $(this).text().trim();

            if($('.selected').length == 0) {
                $('.card').each(function() {
                    $(this).css('display', 'block');
                });
            } else {
                $('.card').each(function() {
                    if($(this).attr('tags').indexOf(tag_name) == -1) $(this).css('display', 'none');
                });
            }

            var count = 0;
            $('.card-link').each(function() {
                if($(this).children('div').css('display') != 'none') count++;
            });

            $('.actions-desktop').children('div').children('div:first').text('Cards total: ' + count);
        });


        $('#btn-sort-newest-first').on('click', function(e) {
            e.preventDefault();
            var cards = [];
            if(window.sorted == 'oldest-first')
                $($('.card-link').get().reverse()).each(function() {
                    cards.push($(this).clone());
                    $(this).remove();
                });
            else
                $('.card-link').each(function() {
                    cards.push($(this).clone());
                    $(this).remove();
                });

            for(var i in cards) {
                cards[i].appendTo('.card-wrapper');
            }

            window.sorted = 'newest-first';
        });


        $('#btn-sort-oldest-first').on('click', function(e) {
            e.preventDefault();

            var cards = [];
            if(window.sorted == 'newest-first')
                $($('.card-link').get().reverse()).each(function() {
                    cards.push($(this).clone());
                    $(this).remove();
                });
            else
                $('.card-link').each(function() {
                    cards.push($(this).clone());
                    $(this).remove();
                });

            for(var i in cards) {
                cards[i].appendTo('.card-wrapper');
            }

            window.sorted = 'oldest-first';
        });

    }

    //---------- REVISE CARDS ----------//

    if($('.revise-card-wrapper').length > 0) {
        var filename = '../data/user' + $('[user-id]').attr('user-id') + '.json';

        // Get JSON
        $.getJSON(filename, function(data) {
            var cards = [];
            var flag = 0;

            // Fill up an array with cards
            for(var i = 0; i < data.length; i++)
                cards.push(data[i]);

            // id -> for card index
            // tags -> to output tags as a formatted str
            var id = 0;
            var tags = '';
            cards[id]['tags'].forEach(function(element) {
                tags += element + ', ';
            });
            tags = tags.substring(0, tags.length - 2);

            // .card-info div:first -> left part of card heading = place for tags str
            // .card-info div:last -> right part = for date and creator id
            if(tags.length <= 70)
                $('.card-info div:first').text('Tags: ' + tags);
            else
                $('.card-info div:first').text('Tags: ' + tags.substring(0, 70) + '...');
            $('.card-info div:last').text('Created on ' + cards[id]['created_at'] + ' by ' + cards[id]['created_by']);

            $('.card').html(cards[id]['content'].decryptSpecialChars());
            $('.score').text('You\'re score on this cards is: ' + cards[id]['score']);


            var spanToInput = function() {
                if($(this).css('display') != 'none') {
                    var elemWidth = $(this).width();
                    var input = '<input name="' + $(this).attr('name') + '" class="q-input" style="width: ' + (elemWidth + 6) + 'px;">';
                    var name = $(this).attr('name');
                    $(this).before(input);
                    $(this).css('display', 'none');

                    setTimeout(function() {
                        $('input[name="' + name + '"]').focus();
                    }, 1);
                } else
                    $('input[name="' + $(this).attr('name') + '"]').focus();

                $('input').on('focusout', checkAnswer);
                $('input').on('keypress', function(event) {
                    if(event.keyCode == '13') {
                        if($('.card').children('span[name="q' + (Number($(this).attr('name').substring(1)) + 1) + '"]').length > 0)
                            $('.card').children('span[name="q' + (Number($(this).attr('name').substring(1)) + 1) + '"]').trigger('mousedown');
                        else $('#btn-know').focus();
                    }
                    if(event.keyCode == '9') {
                        event.preventDefault();
                        if(event.shiftKey) {
                            if($('.card').children('span[name="q' + (Number($(this).attr('name').substring(1)) - 1) + '"]').length > 0)
                                $('.card').children('span[name="q' + (Number($(this).attr('name').substring(1)) - 1) + '"]').trigger('mousedown');
                            else $(window).focus();
                        } else {
                            if($('.card').children('span[name="q' + (Number($(this).attr('name').substring(1)) + 1) + '"]').length > 0)
                                $('.card').children('span[name="q' + (Number($(this).attr('name').substring(1)) + 1) + '"]').trigger('mousedown');
                            else $('#btn-know').focus();
                        }
                    }
                });

            }

            var checkAnswer = function() {
                var spanIndex = 'span[name="' + $(this).attr('name') + '"]';
                if($(this).val().encryptSpecialChars().decryptSpecialChars() == $(spanIndex).text().encryptSpecialChars().decryptSpecialChars()) {
                    if($(this).attr('class').indexOf('right-answer') == -1)
                        $(this).attr('class', 'right-answer');
                } else {
                    if($(this).attr('class').indexOf('wrong-answer') == -1)
                        $(this).attr('class', 'wrong-answer');
                }

                $(window).focus();
            }

            $('.question').on('mousedown', spanToInput);

            $('#btn-know').on('click', function(event) {
                event.preventDefault();

                // Update this card
                cards[id]['score'] = Number(cards[id]['score']) + 1;
                if(cards[id]['last_revised'] == '')
                    cards[id]['last_revised'] = 'new';
                else
                    cards[id]['last_revised'] = 'revised';

                // Show the next card
                id++;
                if(id != cards.length) {
                    tags = '';
                    cards[id]['tags'].forEach(function(element) {
                        tags += element + ', ';
                    });
                    tags = tags.substring(0, tags.length - 2);

                    if(tags.length <= 70)
                        $('.card-info div:first').text('Tags: ' + tags);
                    else
                        $('.card-info div:first').text('Tags: ' + tags.substring(0, 70) + '...');
                    $('.card-info div:last').text('Created on ' + cards[id]['created_at'] + ' by ' + cards[id]['created_by']);

                    $('.card').html(cards[id]['content'].decryptSpecialChars());
                    $('.score').text('You\'re score on this cards is: ' + cards[id]['score']);

                    $('.question').on('mousedown', spanToInput);
                } else {
                    // flag = 1 -> collection completed
                    // flag = 0 -> user has left the revising section (save all revised cards)
                    flag = 1;
                    $('.coll-complete').css('display', 'block');
                }
            });

            $('#btn-dont').on('click', function(event) {
                event.preventDefault();
                if($(this).text().indexOf('Show') != -1) {
                    $('.card').children('span').each(function() {
                        $(this).attr('class', 'answer');
                    });
                    $(this).text("I don't know this");
                }
                else if($(this).text().indexOf('know') != -1) {
                        // Update this card
                        cards[id]['score'] = Number(cards[id]['score']) - 1;
                        if(cards[id]['last_revised'] == '')
                            cards[id]['last_revised'] = 'new';
                        else
                            cards[id]['last_revised'] = 'revised';
                        
                        // Show the next card
                        id++;
                    if(id != cards.length) {
                        tags = '';
                        cards[id]['tags'].forEach(function(element) {
                            tags += element + ', ';
                        });
                        tags = tags.substring(0, tags.length - 2);

                        if(tags.length <= 70)
                            $('.card-info div:first').text('Tags: ' + tags);
                        else
                            $('.card-info div:first').text('Tags: ' + tags.substring(0, 70) + '...');
                        $('.card-info div:last').text('Created on ' + cards[id]['created_at'] + ' by ' + cards[id]['created_by']);
    
                        $('.card').html(cards[id]['content'].decryptSpecialChars());
                        $('.score').text('You\'re score on this cards is: ' + cards[id]['score']);

                        $(this).text("Show the answers");
                    } else {
                        // flag = 1 -> collection completed
                        // flag = 0 -> user has left the revising section (save all revised cards)
                        flag = 1;
                        $('.coll-complete').css('display', 'block');
                    }
                }
            });

            $('#btn-complete-ok').on('click', function() {
                window.location.href = '/';
            });

            $(window).on('unload', function() {
                // when user leaves the revising section in the middle of the revise process - don't update the card he/she currently sees
                if(flag != 1)
                    cards.pop();
                $.nette.ajax({
                    type: 'POST',
                    async: false,
                    url: $('#btn-know').attr('href'),
                    data: {cards:cards},
                    success: function() {
                    }
                });
            });
        })
            .fail(function() {
                $('.no-cards').css('display', 'block');
            });

    }

    //---------- FOLDER SETTINGS ----------//
    if($('.folders-wrapper').length > 0) {


        // Decrypt special chars
        $('.folder-heading span').each(function() {
            $(this).html($(this).html().decryptSpecialChars());
        });


        // tagsinput plugin init. Conf here
        $('#edit-folder-tags').tagsinput({
        });

        $('#edit-folder-tags').on('beforeItemAdd', function(event) {
            if($('#btn-folder-save').is('[folder-id]')) {
                $('.folder').each(function() {
                    if($(this).attr('class').indexOf(' ' + $('#btn-folder-save').attr('folder-id') + '-folder') == -1)
                        $(this).children('.folder-tags').children('div').children('.tag').each(function() {
                            if($(this).html().encryptSpecialChars() == event.item.encryptSpecialChars()) {
                                setTimeout(function() {
                                    $('.bootstrap-tagsinput').children('span:last').attr('class', 'tag nonexist');
                                }, 10);
                            }
                        });
                });
            } else
                $('.tag').each(function() {
                    if($(this).html().encryptSpecialChars() == event.item.encryptSpecialChars()) {
                        setTimeout(function() {
                            $('.bootstrap-tagsinput').children('span:last').attr('class', 'tag nonexist');
                        }, 10);
                    }
                });
        });
        

        $('[name="btn-folder-delete"]').on('click', function() {
            if($('[name="btn-folder-delete"]').length == 1) {
                $('.last-folder-delete').css('display', 'block');
                return false;
            }

            $('.folders-confirm-delete').css('display', 'block');
            $('#btn-confirm-delete').attr('folder-id', $(this).parent().parent().attr('class').substring(7, $(this).parent().parent().attr('class').indexOf('-')));
        });


        $('#btn-confirm-delete').on('click', function(event) {
            event.preventDefault();

            $.nette.ajax({
                type: 'POST',
                url: $(this).attr('href'),
                data: {'delete': $(this).attr('folder-id')},
                success: function() {
                }
            });
        });


        $('#btn-folder-add').on('click', function() {
            $('.folders-edit').css('display', 'block');
        });


        $('[name="btn-folder-edit"]').on('click', function() {
            $('.folders-edit').css('display', 'block');
            $('#btn-folder-save').attr('folder-id', $(this).parent().parent().attr('class').substring(7, $(this).parent().parent().attr('class').indexOf('-')));
            $('#edit-folder-name').val($(this).parent().children('span').text());
            $(this).parent().parent().children('.folder-tags').children('div').children('div').each(function() {
                $('#edit-folder-tags').tagsinput('add', $(this).text());
            });
        });


        $('#btn-folder-save').on('click', function() {
            if($('[folder-id]').length > 0)
                $('#frm-saveFolderForm-id').val($('[folder-id]').attr('folder-id'));

            if($('#edit-folder-name').val() != '')
                $('#frm-saveFolderForm-folder_name').val($('#edit-folder-name').val().encryptSpecialChars());
            else {
                alert('A folder must have a name.');
                $('#edit-folder-name').focus();
                return false;
            }

            $('.nonexist').each(function() {
                $(this).children('[data-role]').remove();
                $('#edit-folder-tags').tagsinput('remove', $(this).html().encryptSpecialChars().decryptSpecialChars());
            });
                
            $('#frm-saveFolderForm-tags').val($('#edit-folder-tags').val());
            $('#frm-saveFolderForm').submit();
        });


        $('#btn-folder-cancel').on('click', function() {
            // Clear all fields after cancel, so they won't reappear
            $('#btn-folder-save').removeAttr('folder-id');
            $('#edit-folder-name').val('');
            $('#edit-folder-tags').tagsinput('removeAll');

            // Just in case
            $('#frm-saveFolderForm-id').val('');
            $('#frm-saveFolderForm-folder_name').val('');
            $('#frm-saveFolderForm-tags').val('');

            $('.modal').css('display', 'none');
        });


        $('#btn-cancel-delete').on('click', function() {
            $('.modal').css('display', 'none');
        });

        $('#btn-last-folder-ok').on('click', function() {
            $('.modal').css('display', 'none');
        });

    }

    //---------- COLLECTIONS ----------//
    if($('.collections-wrapper').length > 0) {
        

        // Init datetimepicker plugins
        $('#datetimepicker1').datetimepicker({
            format: 'DD/MM/YYYY'
        });      
        $('#datetimepicker2').datetimepicker({
            format: 'DD/MM/YYYY'
        });


        $.getJSON('../data/tags.json', function(data) {
            var tagsArr = [];

            for(var i = 0; i < data.length; i++)
                tagsArr.push(data[i]);

            var tags = new Bloodhound({
                datumTokenizer: Bloodhound.tokenizers.whitespace,
                queryTokenizer: Bloodhound.tokenizers.whitespace,
                local: tagsArr
            });

            // tagsinput plugins init. Conf here
            $('#edit-ruleset-tags').tagsinput({
                typeaheadjs: [{
                    hint: true,
                    highlight: true,
                    minLength: 1
                },{
                    name: 'tags',
                    source: tags
                }],
                freeInput: true
            });
            $('#edit-ruleset-tags').on('itemAdded', function() {
                if(tagsArr.indexOf($(this).parent().children('div').children('span:last').prev().text()) != -1)
                    $(this).parent().children('div').children('span:last').prev().attr('class', 'tag exist');
                if(tagsArr.indexOf($(this).parent().children('div').children('span:last').prev().text()) == -1)
                    $(this).parent().children('div').children('span:last').prev().attr('class', 'tag nonexist');
            });
        });

        $.getJSON('../data/users.json', function(data) {
            var usersArr = [];

            for(var i = 0; i < data.length; i++)
                usersArr.push(data[i]);

            var users = new Bloodhound({
                datumTokenizer: Bloodhound.tokenizers.whitespace,
                queryTokenizer: Bloodhound.tokenizers.whitespace,
                local: usersArr
            });

            // tagsinput plugins init. Conf here
            $('#edit-ruleset-users').tagsinput({
                typeaheadjs: [{
                    hint: true,
                    highlight: true,
                    minLength: 1
                },{
                    name: 'users',
                    source: users
                }],
                freeInput: true
            });
            $('#edit-ruleset-users').on('itemAdded', function() {
                if(usersArr.indexOf($(this).parent().children('div').children('span:last').prev().text()) != -1)
                    $(this).parent().children('div').children('span:last').prev().attr('class', 'tag exist');
                if(usersArr.indexOf($(this).parent().children('div').children('span:last').prev().text()) == -1)
                    $(this).parent().children('div').children('span:last').prev().attr('class', 'tag nonexist');
            });
        });
        
        $('#btn-coll-cancel').on('click', function() {
            // Empty custom fields
            $('#btn-coll-save').removeAttr('coll-id');
            $('#edit-ruleset-tags').tagsinput('removeAll');
            $('#edit-ruleset-users').tagsinput('removeAll');
            $('#datetimepicker1 input').val('');
            $('#datetimepicker2 input').val('');

            // Empty form's fields
            $('#frm-saveRulesetForm-id').val('');
            $('#frm-saveRulesetForm-tags').val('');
            $('#frm-saveRulesetForm-users').val('');
            $('#frm-saveRulesetForm-date_from').val('');
            $('#frm-saveRulesetForm-date_to').val('');

            $('.modal').css('display', 'none');
        });


        $('#btn-coll-add').on('click', function() {
            $('.ruleset-edit').css('display', 'block');
        });


        $('#btn-coll-save').on('click', function() {
            if($('[coll-id]').length > 0)
                $('#frm-saveRulesetForm-id').val($('[coll-id]').attr('coll-id'));

            if($('#edit-ruleset-name').val() != '')
                $('#frm-saveRulesetForm-ruleset_name').val($('#edit-ruleset-name').val());
            else {
                alert('A collection must have a name.');
                $('#edit-ruleset-name').focus();
                return false;
            }

            $('#frm-saveRulesetForm-tags').val($('#edit-ruleset-tags').val());
            $('#frm-saveRulesetForm-users').val($('#edit-ruleset-users').val());
            $('#frm-saveRulesetForm-date_from').val($('#datetimepicker1 input').val());
            $('#frm-saveRulesetForm-date_to').val($('#datetimepicker2 input').val());

            $('#frm-saveRulesetForm').submit();
        });


        $('[name="btn-coll-delete"]').on('click', function() {
            $('.coll-confirm-delete').css('display', 'block');
            $('#btn-confirm-delete').attr('ruleset-id', $(this).parent().parent().attr('class').substring(8, $(this).parent().parent().attr('class').indexOf('-')));
        });


        $('#btn-cancel-delete').on('click', function() {
            $('.modal').css('display', 'none');
        });


        $('#btn-confirm-delete').on('click', function(event) {
            event.preventDefault();

            $.nette.ajax({
                type: 'POST',
                url: $(this).attr('href'),
                data: {'delete': $(this).attr('ruleset-id')},
                success: function() {
                }
            });
        });


        $('[name="btn-coll-edit"]').on('click', function() {
            $('.ruleset-edit').css('display', 'block');

            // Fill in with current collection ruleset data
            $('#btn-coll-save').attr('coll-id', $(this).parent().parent().attr('class').substring(8, $(this).parent().parent().attr('class').indexOf('-')));
            $('#edit-ruleset-name').val($(this).parent().children('span').text());
            if($(this).parent().parent().children('.ruleset-info').children('div:first').text().trim() != 'Tags: all')
                $(this).parent().parent().children('.ruleset-info').children('div:first').children('div').each(function() {
                    $('#edit-ruleset-tags').tagsinput('add', $(this).text());
                });
            if($(this).parent().parent().children('.ruleset-info').children('div:first').next().text().trim() != 'Users: all')
                $(this).parent().parent().children('.ruleset-info').children('div:first').next().children('div').each(function() {
                    $('#edit-ruleset-users').tagsinput('add', $(this).text());
                });
            if($(this).parent().parent().children('.ruleset-info').children('div:last').text().indexOf('from') != -1)
                $('#datetimepicker1 input').val($(this).parent().parent().children('.ruleset-info').children('div:last').text().substring($(this).parent().parent().children('.ruleset-info').children('div:last').text().indexOf('from') + 5, $(this).parent().parent().children('.ruleset-info').children('div:last').text().indexOf(' - ')).split('.').join('/').trim());
            if($(this).parent().parent().children('.ruleset-info').children('div:last').text().indexOf('to') != -1)
                $('#datetimepicker2 input').val($(this).parent().parent().children('.ruleset-info').children('div:last').text().substring($(this).parent().parent().children('.ruleset-info').children('div:last').text().indexOf('to') + 3).split('.').join('/').trim());

        });

    }

});


//---------- AUX FNS ----------//
String.prototype.encryptSpecialChars = function() {
    var result;
    result = this.split('&lt;').join('~lt~');
    result = result.split('&gt;').join('~gt~');
    result = result.split('&amp;').join('~amp~');
    result = result.split('&quot;').join('~\'~');
    result = result.split('<').join('~lt~');
    result = result.split('>').join('~gt~');
    result = result.split('\'').join('~sq~');
    result = result.split('\"').join('~dq~');
    result = result.split('\\').join('~bs~');
    result = result.split('|').join('~or~');
    result = result.split('&').join('~amp~');
    result = result.split(';').join('~sc~');
    return result;
}

String.prototype.decryptSpecialChars = function() {
    var result;
    result = this.split('~lt~span').join('<span');
    result = result.split('~lt~/span~gt~').join('</span>');
    result = result.split('~lt~br~gt~').join('<br>');
    result = result.split('~lt~').join('&lt;');
    result = result.split('~gt~').join('>');
    result = result.split('~amp~').join('&');
    result = result.split('~sq~').join('\'');
    result = result.split('~dq~').join('\"');
    result = result.split('~bs~').join('\\');
    result = result.split('~fs~').join('/');
    result = result.split('~or~').join('|');
    result = result.split('~sc~').join(';');
    return result;
}

function replaceSelectedText(replacementText) {
    var sel, range;
    if (window.getSelection) {
        sel = window.getSelection();
        if (sel.rangeCount) {
            range = sel.getRangeAt(0);
            range.deleteContents();
            range.insertNode(document.createTextNode(replacementText));
        }
    } else if (document.selection && document.selection.createRange) {
        range = document.selection.createRange();
        range.text = replacementText;
    }
}

function date_sort_asc(date1, date2) {
  if (date1 > date2) return 1;
  if (date1 < date2) return -1;
  return 0;
};

function date_sort_desc(date1, date2) {
  if (date1 > date2) return -1;
  if (date1 < date2) return 1;
  return 0;
};
