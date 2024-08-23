/*
 *  Copyright 2024.  Baks.dev <admin@baks.dev>
 *
 *  Permission is hereby granted, free of charge, to any person obtaining a copy
 *  of this software and associated documentation files (the "Software"), to deal
 *  in the Software without restriction, including without limitation the rights
 *  to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 *  copies of the Software, and to permit persons to whom the Software is furnished
 *  to do so, subject to the following conditions:
 *
 *  The above copyright notice and this permission notice shall be included in all
 *  copies or substantial portions of the Software.
 *
 *  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 *  IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 *  FITNESS FOR A PARTICULAR PURPOSE AND NON INFRINGEMENT. IN NO EVENT SHALL THE
 *  AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 *  LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 *  OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 *  THE SOFTWARE.
 */

// проверка открыто ли модальное окно - вне модального окна скрипт работать не будет!
if (document.getElementById('avito_images_modal')) {
    /* кнопка Добавить ФОТО */
    let $addButtonPhoto = document.getElementById('photo_addCollection');

    /* Блок для новой коллекции */
    let $blockCollectionPhoto = document.getElementById('photo_collection');

    if ($addButtonPhoto) {
        /* добавить событие на удаление ко всем существующим элементам формы в блок с классом .del-item */
        let $delItemPhoto = $blockCollectionPhoto.querySelectorAll('.del-item-photo');

        /* Удаляем при клике коллекцию СЕКЦИЙ */
        $delItemPhoto.forEach(function (item) {
            item.addEventListener('click', function () {
                let $counter = $blockCollectionPhoto.getElementsByClassName('item-collection-photo').length;
                if ($counter > 1) {
                    item.closest('.item-collection-photo').remove();
                }
            });
        });

        let $changeItemPhoto = $blockCollectionPhoto.querySelectorAll('.change-root');

        $changeItemPhoto.forEach(function (item) {

            if ($changeItemPhoto.length === 1 && item.checked === false) {
                item.checked = true;
            }

            item.addEventListener('change', function () {

                let photo_collection = document.getElementById('photo_collection');

                photo_collection.querySelectorAll('.change-root').forEach(function (rootCheck) {
                    rootCheck.checked = false;
                });


                this.checked = true;
            });
        });

        /* Добавляем новую коллекцию */
        $addButtonPhoto.addEventListener('click', function () {

            let $addButtonPhoto = this;

            /* получаем прототип коллекции  */
            let newForm = document.getElementById($addButtonPhoto.dataset.prototype).dataset.prototype;
            let index = $addButtonPhoto.dataset.index * 1;

            if (index === 6) {
                return;
            }

            /* Замена '__name__' в HTML-коде прототипа число, основанное на том, сколько коллекций */
            newForm = newForm.replace(/__photos__/g, index);

            /* Вставляем новую коллекцию */
            let div = document.createElement('div');
            div.classList.add('item-collection-photo')
            div.innerHTML = newForm;
            $blockCollectionPhoto.append(div);

            /* Удаляем при клике коллекцию СЕКЦИЙ */
            div.querySelector('.del-item-photo').addEventListener('click', function () {
                let $counter = $blockCollectionPhoto.getElementsByClassName('item-collection-photo').length;
                //if ($counter > 1) {
                this.closest('.item-collection-photo').remove();
                let index = $addButtonPhoto.dataset.index * 1;
                $addButtonPhoto.dataset.index = (index - 1).toString()

                //}
            });

            div.querySelector('.change-root').addEventListener('change', function (selector) {

                let photo_collection = document.getElementById('photo_collection');

                photo_collection.querySelectorAll('.change-root').forEach(function (rootChack, i, arr) {
                    rootChack.checked = false;
                });

                this.checked = true;
            });

            /* Увеличиваем data-index на 1 после вставки новой коллекции */
            $addButtonPhoto.dataset.index = (index + 1).toString();

            let inputElement = div.querySelector('input[type="file"]');

            inputElement.addEventListener('change', function (e) {

                var file = inputElement.files[0];
                var reader = new FileReader();
                let image = div.querySelector('.image-input');

                reader.onloadend = function () {

                    image.style.setProperty("background-image", "url(" + reader.result + ")", "important")
                }

                if (file) {
                    reader.readAsDataURL(file);
                } else {
                    image.style.setProperty("background-image", "url(/img/blank.svg)", "important")

                }
            });
        });
    }
}

