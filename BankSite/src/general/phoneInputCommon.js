import IMask from "imask";
import { parsePhoneNumber } from "awesome-phonenumber";

const getPhoneMaskInstance = (elementRef) => IMask(elementRef.value, {
    mask: [
        {
            mask: '+1 (000) 000 0000',
            startsWith: '1',
            lazy: false,
            placeholderChar: "-",
            country: 'USA'
        },
        {
            mask: '+7 (000) 000 00 00',
            startsWith: '7',
            lazy: false,
            placeholderChar: "-",
            country: 'Russia'
        },
        {
            mask: '+44 (0000) 000000',
            startsWith: '44',
            lazy: false,
            placeholderChar: "-",
            country: 'United Kingdom'
        },
        {
            mask: '+000000000000000', // Allows up to 15 digits
            startsWith: '',
            country: 'unknown'
        }
    ],
    dispatch: function (appended, dynamicMasked) {
        const number = (dynamicMasked.value + appended).replace(/\D/g,'');

        const foundMask = dynamicMasked.compiledMasks.find(function (m) {
            return number.startsWith(m.startsWith);
        });

        return foundMask;
    }
});

const validatePhoneInput = (phoneMask) => {
    const unmaskedValue = phoneMask.unmaskedValue;
    if (unmaskedValue.length > 3) { 
        const phoneNumber = parsePhoneNumber('+' + unmaskedValue);
        console.log(phoneNumber);
        if (phoneNumber && phoneNumber.valid) {
            console.log('Valid number entered:', phoneNumber.formatInternational());
        }
    }
}

const initiate = (phoneMask) => {
    phoneMask.on('accept', () => {
        if (!phoneMask.value.startsWith('+')) {
            phoneMask.value = '+' + phoneMask.unmaskedValue;
        }
    });
}

export {
    initiate, validatePhoneInput, getPhoneMaskInstance
};