<?php

namespace Komodo\RajaOngkir\Constants;

enum Courier: string
{
    /** POS Indonesia */
    case POS_INDONESIA = 'pos';

    /** Lion Parcel */
    case LION_PARCEL = 'lion';

    /** Ninja Xpress */
    case NINJA_XPRESS = 'ninja';

    /** ID Express */
    case ID_EXPRESS = 'ide';

    /** SiCepat Express */
    case SICEPAT = 'sicepat';

    /** SAP Express */
    case SAP_EXPRESS = 'sap';

    /** Royal Express Indonesia */
    case REX = 'rex';

    /** Sentral Cargo */
    case SENTRAL = 'sentral';

    /** Jalur Nugraha Ekakurir */
    case JNE = 'jne';

    /** Citra Van Titipan Kilat */
    case TIKI = 'tiki';

    /** Wahana Prestasi Logistik */
    case WAHANA = 'wahana';

    /** J&T Express */
    case JNT = 'jnt';
}
