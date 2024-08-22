/*
 *  Copyright 2023.  Baks.dev <admin@baks.dev>
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

/* кнопка Добавить торговое предложение */
let $btnAddOffer = document.getElementById('offer_addCollection');


if ($btnAddOffer) {

    let $blockCollectionOffers = document.getElementById('collection-offer');

    /* Добавляем новую коллекцию торгового предложения */
    $btnAddOffer.addEventListener('click', function () {

        /* получаем прототип коллекции  */
        /*let newForm = this.dataset.prototype;*/
        let newForm =  document.getElementById(this.dataset.prototype).dataset.prototype;

        let index = this.dataset.index * 1;

        newForm = newForm.replace(/__offers__/g, index);
        //newForm = newForm.replace(/__offer_variation__/g, 0);


        /* Вставляем новую коллекцию */
        let div = document.createElement('div');
        div.classList.add('card');
        div.classList.add('mb-3');
        div.classList.add('w-100');
        //div.classList.add('border-light');

        div.id = 'item-collection-offer-' + index;
        div.innerHTML = newForm;

        $blockCollectionOffers.append(div);


        /* Удаляем при клике торговое предложение */
        div.querySelector('.del-item-offer').addEventListener('click', deleteOffer);


        /* Делаем замену если торговое предложение - справочник */
        let $inputValue = div.querySelector('#product_form_offer_' + index + '_value');
        replaceReference($inputValue, 'product_form_data-offer-reference');


        /* Событие на Добавить еще фото в ТП */
        (div.querySelector('.offer-image-add-collection'))?.addEventListener('click', addOfferImage);


        /* Присваиваем идентификатор categoryOffer*/
        //let valueCategoryOffer = document.getElementById('product_form_data-offer').value;
        //let $categoryOffer = div.querySelector('#product_form_offer_' + index + '_categoryOffer').value = valueCategoryOffer;

        /* document.querySelectorAll('[class^="change-root-"]').forEach(function (item) {
             item.addEventListener('change', chanheOfferImageRoot);
         });*/


        let $changeRoot = div.querySelector('[class^="change-root-"]');
        if ($changeRoot) {
            $changeRoot.checked = true;
            $changeRoot.addEventListener('change', chanheOfferImageRoot);
        }


        /* Удаляем при клике фото торгового предложения */
        (div.querySelector('.del-item-offer-image'))?.addEventListener('click', deleteOfferImage);


        /* Плавная прокрутка к элементу */
        div.scrollIntoView({block: "center", inline: "center", behavior: "smooth"});

        this.dataset.index = (index + 1).toString();


        /** МНОЖЕСТВЕННЫЙ ВАРИАНТ */


        /* Удаляем при клике множественный вариант */
        (div.querySelector('.del-item-variation'))?.addEventListener('click', deleteVariation);


        /* Делаем замену если множественный вариант - справочник */
        let $inputValueVariation = div.querySelector('#product_form_offer_' + index + '_variation_0_value');
        replaceReference($inputValueVariation, 'product_form_data-variation-reference');


        /* Событие на добавить фото множественного варианта */
        (div.querySelector('.variation-image-add-collection'))?.addEventListener('click', addVariationImage);

        /* Удаляем при клике фото множественного варианта предложения */
        (div.querySelector('.del-item-variation-image'))?.addEventListener('click', deleteVariationImage);

        /* События change-root image ВАРИАНТА */
        let changeRadio = div.querySelector('.change-variation-product_form_offer_' + index + '_variation_0');
        if (changeRadio) {
            changeRadio.checked = true;
            changeRadio.addEventListener('change', chanheVariationImageRoot);
        }

        (div.querySelector('.offer-variation-add-collection'))?.addEventListener('click', addVariation);


        /** МОДИФИКАЦИЯ */

        /* Событие Добавить модификатор множественного варианта */
        (div.querySelector('.variation-modification-add-collection'))?.addEventListener('click', addModification);

        /* Удаляем при клике модификацию */
        (div.querySelector('.del-item-modification'))?.addEventListener('click', deleteModification);


        /* Делаем замену если модификация - справочник */
        let $inputValueModification = div.querySelector('#product_form_offer_' + index + '_variation_0_modification_0_value');
        replaceReference($inputValueModification, 'product_form_data-modification-reference');


        /* Событие на добавить фото модификации */
        (div.querySelector('.modification-image-add-collection'))?.addEventListener('click', addModificationImage);

        /* Удаляем при клике фото модификации */
        (div.querySelector('.del-item-modification-image'))?.addEventListener('click', deleteModificationImage);

        /* События change-root image */
        let changeRadioModification = div.querySelector('.change-modification-product_form_offer_' + index + '_variation_0_modification_0');
        if (changeRadioModification) {
            changeRadioModification.checked = true;
            changeRadioModification.addEventListener('change', chanheModificationImageRoot);
        }


        /* Генератор артикулов  */
        (div.querySelector('.article-generate'))?.addEventListener('click', articleGenerate);

        /* Вызываем предпросмотрт фото */
        offerPreloadPhoto(div);

        /* let inputElement = div.querySelector('input[type="file"]');

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
         });*/

    });
}

/** Удаляем  торговое предложение */

document.querySelectorAll('.del-item-offer').forEach(function (item) {
    item.addEventListener('click', deleteOffer);
});

function deleteOffer() {
    document.getElementById('item-collection-offer-' + this.dataset.index).remove();
}


/** Добавить фото торгового предложения */

document.querySelectorAll('.offer-image-add-collection').forEach(function (item) {
    item.addEventListener('click', addOfferImage);
});

function addOfferImage() {


    /* Получаем прототип формы */
    //let newForm = this.dataset.prototype;
    let newForm = document.getElementById(this.dataset.prototype).dataset.prototype;

    let index = this.dataset.index * 1;
    let offer = this.dataset.offer * 1;


    // newForm = newForm.replace(/__name__/g, name) /* меняем индекс торгового предложения */

    newForm = newForm.replace(/__offers__/g, offer)
    //newForm = newForm.replace(/__variation__/g, offer)
    newForm = newForm.replace(/__offer_image__/g, index)

    let div = document.createElement('div');
    div.innerHTML = newForm;
    div.id = 'item_product_form_offer_' + offer + '_image_' + index;

    $offerImageCollection = document.getElementById('offer_image_collection-' + offer);
    $offerImageCollection.append(div);

    /* Удаляем при клике фото торгового предложения */
    div.querySelector('.del-item-offer-image').addEventListener('click', deleteOfferImage);

    /** обытия change-root image */
    div.querySelector('.change-root-' + offer).addEventListener('change', chanheOfferImageRoot);

    this.dataset.index = (index + 1).toString();


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

}


/** Удалить фото торгового предложения */

document.querySelectorAll('.del-item-offer-image').forEach(function (item) {
    item.addEventListener('click', deleteOfferImage);
});

function deleteOfferImage() {

    /* Если удаляется фото ROOT - выделяем следующую кнопку */

    let btnRootId = this.id.replace(/DeleteOfferPhoto/g, 'root');
    let btnRoot = document.getElementById(btnRootId);
    let photoBlock = document.getElementById(this.dataset.index);

    let parentElement = photoBlock.parentElement;

    photoBlock.remove();

    if (btnRoot) {
        if (btnRoot.checked == true) {
            /* Выделяем первый элемент в родительском блоке */
            let nextChaecked = parentElement.querySelector('[class^="change-root-"]');
            if (nextChaecked) {
                nextChaecked.checked = true;
            }
        }
    }


}


/** Меняем CHACKED изображений радио-кнопок Root */

document.querySelectorAll('[class^="change-root-"]').forEach(function (item) {
    item.addEventListener('change', chanheOfferImageRoot);
});

function chanheOfferImageRoot() {

    let offer_photo_collection = document.getElementById('offer_image_collection-' + this.dataset.index);

    offer_photo_collection.querySelectorAll('.change-root-' + this.dataset.index).forEach(function (rootChack, i, arr) {
        rootChack.checked = false;
    });

    this.checked = true;
}


/* VARIATION *********************************************************************************************************  **/

/** Добавить множественный вариант */


document.querySelectorAll('.offer-variation-add-collection').forEach(function (item) {
    item.addEventListener('click', addVariation);
});

function addVariation() {

    /* Получаем прототип формы */
    //let newForm = this.dataset.prototype;
    let newForm = document.getElementById(this.dataset.prototype).dataset.prototype;
    let index = this.dataset.index * 1;
    let offer = this.dataset.offer * 1;


    // __offers__
    // __offer_variation__

    newForm = newForm.replace(/__offers__/g, offer)
    newForm = newForm.replace(/__offer_variation__/g, index)


    let div = document.createElement('div');
    div.innerHTML = newForm;
    div.id = 'item_product_form_offer_' + offer + '_variation_' + index;


    /* Удаляем при клике множественный вариант */
    div.querySelector('.del-item-variation').addEventListener('click', deleteVariation);


    /* Делаем замену если множественный вариант - справочник */
    let $inputValue = div.querySelector('#product_form_offer_' + offer + '_variation_' + index + '_value');
    replaceReference($inputValue, 'product_form_data-variation-reference');


    /* Событие на добавить фото множественного варианта */
    (div.querySelector('.variation-image-add-collection'))?.addEventListener('click', addVariationImage);

    /* Удаляем при клике фото множественного варианта предложения */
    (div.querySelector('.del-item-variation-image'))?.addEventListener('click', deleteVariationImage);

    /* События change-root image */
    let changeRadio = div.querySelector('.change-variation-product_form_offer_' + offer + '_variation_' + index);
    if (changeRadio) {
        changeRadio.checked = true;
        changeRadio.addEventListener('change', chanheVariationImageRoot);
    }


    /** МОДИФИКАЦИЯ */

    /* Событие Добавить модификатор множественного варианта */
    (div.querySelector('.variation-modification-add-collection'))?.addEventListener('click', addModification);

    /* Удаляем при клике модификацию */
    (div.querySelector('.del-item-modification'))?.addEventListener('click', deleteModification);


    /* Делаем замену если модификация - справочник */
    let $inputValueModification = div.querySelector('#product_form_offer_' + offer + '_variation_' + index + '_modification_0_value');
    replaceReference($inputValueModification, 'product_form_data-modification-reference');


    /* Событие на добавить фото модификации */
    (div.querySelector('.modification-image-add-collection'))?.addEventListener('click', addModificationImage);

    /* Удаляем при клике фото модификации */
    (div.querySelector('.del-item-modification-image'))?.addEventListener('click', deleteModificationImage);

    /* События change-root image */
    let changeRadioModification = div.querySelector('.change-modification-product_form_offer_' + offer + '_variation_' + index + '_modification_0');
    if (changeRadioModification) {
        changeRadioModification.checked = true;
        changeRadioModification.addEventListener('change', chanheModificationImageRoot);
    }


    /* Выделяем Root если элемент добавлен новый */
    let $chanheModificationImageRoot = div.querySelectorAll('[class^="change-modification-"]');
    if ($chanheModificationImageRoot.length === 1) {
        $chanheModificationImageRoot.forEach(function (item) {
            item.checked = true;
        });
    }


    let $collection = document.getElementById(this.dataset.collection);
    $collection.append(div);


    /* Генератор артикулов  */
    (div.querySelector('.article-generate'))?.addEventListener('click', articleGenerate);

    /* Вызываем предпросмотрт фото */
    offerPreloadPhoto(div);


    this.dataset.index = (index + 1).toString();


}


/** Удаляем  множественный вариант  */

document.querySelectorAll('.del-item-variation').forEach(function (item) {
    item.addEventListener('click', deleteVariation);
});

function deleteVariation() {
    document.getElementById(this.dataset.index).remove();
}


/** Добавить фото множественного варианта */

document.querySelectorAll('.variation-image-add-collection').forEach(function (item) {
    item.addEventListener('click', addVariationImage);
});

function addVariationImage() {


    /* Получаем прототип формы */
    //let newForm = this.dataset.prototype;
    let newForm = document.getElementById(this.dataset.prototype).dataset.prototype;

    let index = this.dataset.index * 1;
    let offer = this.dataset.offer * 1;
    let variation = this.dataset.variation * 1;

    let id = this.dataset.id;


    // newForm = newForm.replace(/__name__/g, name) /* меняем индекс торгового предложения */

    newForm = newForm.replace(/__offers__/g, offer)
    newForm = newForm.replace(/__offer_variation__/g, variation)
    newForm = newForm.replace(/__variation_image__/g, index)

    let div = document.createElement('div');
    div.innerHTML = newForm;
    div.id = 'item_product_form_offer_' + offer + '_variation_' + variation + '_image_' + index;

    let $collection = document.getElementById('collection_' + id);
    $collection.append(div);

    /* Удаляем при клике фото множественного варианта предложения */
    div.querySelector('.del-item-variation-image').addEventListener('click', deleteVariationImage);

    /* События change-root image */
    div.querySelector('.change-variation-' + id).addEventListener('change', chanheVariationImageRoot);

    /* Вызываем предпросмотрт фото */
    offerPreloadPhoto(div);


    this.dataset.index = (index + 1).toString();


    /*let inputElement = div.querySelector('input[type="file"]');

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
    });*/

}


/** Удалить фото множественного варианта */

document.querySelectorAll('.del-item-variation-image').forEach(function (item) {
    item.addEventListener('click', deleteVariationImage);
});

function deleteVariationImage() {

    /* Если удаляется фото ROOT  - выделяем следующую кнопку */

    let btnRootId = this.id.replace(/DeleteVariationPhoto/g, 'root');
    let btnRoot = document.getElementById(btnRootId);
    let photoBlock = document.getElementById(this.dataset.index);

    let parentElement = photoBlock.parentElement;


    photoBlock.remove();

    if (btnRoot) {
        if (btnRoot.checked == true) {

            /* Выделяем первый элемент в родительском блоке */
            let nextChaecked = parentElement.querySelector('[class^="change-variation-"]');
            if (nextChaecked) {
                nextChaecked.checked = true;
            }
        }
    }


}


/** Меняем CHACKED изображений множественных вариантов радио-кнопок Root */

document.querySelectorAll('[class^="change-variation-"]').forEach(function (item) {
    item.addEventListener('change', chanheVariationImageRoot);
});

function chanheVariationImageRoot() {

    let variation_photo_collection = document.getElementById('collection_' + this.dataset.index);

    variation_photo_collection.querySelectorAll('.change-variation-' + this.dataset.index).forEach(function (rootChack, i, arr) {
        rootChack.checked = false;
    });

    this.checked = true;

}












/* MODIFICATION *********************************************************************************************************  **/


document.querySelectorAll('.variation-modification-add-collection').forEach(function (item) {
    item.addEventListener('click', addModification);
});

function addModification() {

    /* Получаем прототип формы */
    //let newForm = this.dataset.prototype;
    let newForm = document.getElementById(this.dataset.prototype).dataset.prototype;

    let index = this.dataset.index * 1;
    let offer = this.dataset.offer * 1;
    let variation = this.dataset.variation * 1;


    // __offers__
    // __offer_variation__

    newForm = newForm.replace(/__offers__/g, offer)
    newForm = newForm.replace(/__offer_variation__/g, variation)
    newForm = newForm.replace(/__variation_modification__/g, index)


    let div = document.createElement('div');
    div.innerHTML = newForm;
    div.id = 'item_product_form_offer_' + offer + '_variation_' + variation + '_modification_' + index;


    /* Удаляем при клике модификацию */
    div.querySelector('.del-item-modification').addEventListener('click', deleteModification);


    /* Делаем замену если модификация - справочник */
    let $inputValue = div.querySelector('#product_form_offer_' + offer + '_variation_' + variation + '_modification_' + index + '_value');
    replaceReference($inputValue, 'product_form_data-modification-reference');


    /* Событие на добавить фото модификации */
    (div.querySelector('.modification-image-add-collection'))?.addEventListener('click', addModificationImage);

    /* Удаляем при клике фото модификации */
    (div.querySelector('.del-item-modification-image'))?.addEventListener('click', deleteModificationImage);

    /* События change-root image */
    let changeRadio = div.querySelector('.change-modification-product_form_offer_' + offer + '_variation_' + variation + '_modification_' + index);
    if (changeRadio) {
        changeRadio.checked = true;
        changeRadio.addEventListener('change', chanheModificationImageRoot);
    }


    let $collection = document.getElementById(this.dataset.collection);
    $collection.append(div);

    /* Генератор артикулов  */
    (div.querySelector('.article-generate'))?.addEventListener('click', articleGenerate);

    /* Вызываем предпросмотрт фото */
    offerPreloadPhoto(div);


    this.dataset.index = (index + 1).toString();


}


/** Удаляем  модификацию  */

document.querySelectorAll('.del-item-modification').forEach(function (item) {
    item.addEventListener('click', deleteModification);
});

function deleteModification() {
    document.getElementById(this.dataset.index).remove();
}

/** Добавить фото модификацию */

document.querySelectorAll('.modification-image-add-collection').forEach(function (item) {
    item.addEventListener('click', addModificationImage);
});

function addModificationImage() {

    /* Получаем прототип формы */
    //let newForm = this.dataset.prototype;
    let newForm = document.getElementById(this.dataset.prototype).dataset.prototype;

    let index = this.dataset.index * 1;
    let offer = this.dataset.offer * 1;
    let variation = this.dataset.variation * 1;
    let modification = this.dataset.modification * 1;

    let id = this.dataset.id;


    // newForm = newForm.replace(/__name__/g, name) /* меняем индекс торгового предложения */

    newForm = newForm.replace(/__offers__/g, offer)
    newForm = newForm.replace(/__offer_variation__/g, variation)
    newForm = newForm.replace(/__variation_modification__/g, modification)
    newForm = newForm.replace(/__modification_image__/g, index)

    let div = document.createElement('div');
    div.innerHTML = newForm;
    div.id = 'item_product_form_offer_' + offer + '_variation_' + variation + '_modification_' + modification + '_image_' + index;

    let $collection = document.getElementById('collection_' + id);
    $collection.append(div);

    /* Удаляем при клике фото множественного варианта предложения */
    div.querySelector('.del-item-modification-image').addEventListener('click', deleteModificationImage);

    /* События change-root image */
    div.querySelector('.change-modification-' + id).addEventListener('change', chanheModificationImageRoot);


    /* Выделяем Root если элемент добавлен новый */
    /*$chanheModificationImageRoot = div.querySelectorAll('[class^="change-modification-"]');

    if ($chanheModificationImageRoot.length === 1) {
        $chanheModificationImageRoot.forEach(function (item) {
            item.checked = true;
        });
    }*/

    //console.log(document.querySelectorAll('[class^="change-modification-"]').length);

    /* Вызываем предпросмотрт фото */
    offerPreloadPhoto(div);


    this.dataset.index = (index + 1).toString();


    /*let inputElement = div.querySelector('input[type="file"]');

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
    });*/

}


/** Удалить фото модификации */

document.querySelectorAll('.del-item-modification-image').forEach(function (item) {
    item.addEventListener('click', deleteModificationImage);
});

function deleteModificationImage() {

    /* Если удаляется фото ROOT  - выделяем следующую кнопку */

    let btnRootId = this.id.replace(/DeleteModificationPhoto/g, 'root');
    let btnRoot = document.getElementById(btnRootId);
    let photoBlock = document.getElementById(this.dataset.index);

    let parentElement = photoBlock.parentElement;


    photoBlock.remove();

    if (btnRoot) {
        if (btnRoot.checked == true) {

            /* Выделяем первый элемент в родительском блоке */
            let nextChaecked = parentElement.querySelector('[class^="change-modification-"]');
            if (nextChaecked) {
                nextChaecked.checked = true;
            }
        }
    }
}


/** Меняем CHACKED изображений модификации радио-кнопок Root */

document.querySelectorAll('[class^="change-modification-"]').forEach(function (item) {
    item.addEventListener('change', chanheModificationImageRoot);
});

function chanheModificationImageRoot() {

    let modification_photo_collection = document.getElementById('collection_' + this.dataset.index);

    modification_photo_collection.querySelectorAll('.change-modification-' + this.dataset.index).forEach(function (rootChack, i, arr) {
        rootChack.checked = false;
    });

    this.checked = true;

}


function offerPreloadPhoto(div) {
    //let inputElement = div.querySelector('input[type="file"]');
    let inputElements = div.querySelectorAll('input[type="file"]');

    inputElements.forEach(function (inputElement, i, arr) {
        inputElement.addEventListener('change', function (e) {

            var file = this.files[0];
            var reader = new FileReader();
            //let image = div.querySelector('.image-input');

            let image = this.closest('.image-input')

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


/** Метод делает замену справочника  */
function replaceReference($replace, $id) {

    // product_form_data-variation-reference

    /* Получаем дефолтное поле для заполнения  */
    let $reference = document.getElementById($id);

    if ($reference === null || $reference.tagName != 'SELECT') {
        return;
    }

    if ($reference) {

        // $searchBlock.querySelectorAll('[data-reference]').forEach(function (item) {

        let cloneNode = $reference.cloneNode(true);

        cloneNode.id = $replace.id;
        cloneNode.name = $replace.name;
        cloneNode.value = "";
        //cloneNode.removeAttribute('style');

        if (cloneNode.options !== undefined) {
            cloneNode.options[0].selected = true;
            cloneNode.options[0].selected = 'selected';
            cloneNode.selectedIndex = 0;

            /* Сбрасываем select */
            for (let i = 0, l = cloneNode.options.length; i < l; i++) {
                cloneNode.options[i].removeAttribute('selected');
                cloneNode.options[i].selected = false;
            }
        }


        $replace.replaceWith(cloneNode);

        /* применяем select2 */
        //if (cloneNode.dataset.select === "select2") {
        new NiceSelect(cloneNode, {searchable: true});
        //}

    }
}

document.querySelectorAll('.article-generate').forEach(function (item) {
    item.addEventListener('click', articleGenerate);
});

function articleGenerate() {

    let generate = '';

    /**Получаем артикул товара */
    product = document.getElementById('product_form_info_article');
    if (product) {
        generate += product.value;
    }

    if (this.dataset.offer) {
        let article = document.getElementById(this.dataset.offer);

        if (article && article.value.length) {
            generate += (generate.length ? '-' : '') + article.value;
        }

    }


    if (this.dataset.offerpostfix) {
        article = document.getElementById(this.dataset.offerpostfix);

        if (article && article.value.length) {
            generate += (generate.length ? '-' : '') + article.value;
        }
    }

    if (this.dataset.variation) {
        article = document.getElementById(this.dataset.variation);

        if (article && article.value.length) {
            generate += (generate.length ? '-' : '') + article.value;
        }
    }

    if (this.dataset.variationpostfix) {
        article = document.getElementById(this.dataset.variationpostfix);

        if (article && article.value.length) {
            generate += (generate.length ? '-' : '') + article.value;
        }
    }


    if (this.dataset.modification) {
        article = document.getElementById(this.dataset.modification);

        if (article && article.value.length) {
            generate += (generate.length ? '-' : '') + article.value;
        }
    }


    if (this.dataset.modificationpostfix) {
        article = document.getElementById(this.dataset.modificationpostfix);

        if (article && article.value.length) {
            generate += (generate.length ? '-' : '') + article.value;
        }
    }


    result = document.getElementById(this.dataset.id);

    if (result) {
        result.value = generate.replace(/[^a-zA-Z0-9]/g, '-');
    }

}