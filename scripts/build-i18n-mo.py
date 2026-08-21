#!/usr/bin/env python3
from pathlib import Path
import struct

ROOT = Path(__file__).resolve().parents[1] / 'theme' / 'languages'
ROOT.mkdir(parents=True, exist_ok=True)

translations = {
    'ar': {
        'Bien': 'عقار', 'Caractéristiques': 'الخصائص', 'Description': 'الوصف',
        'Contacter le vendeur': 'تواصل مع المعلن', 'Déposer une annonce': 'إضافة إعلان',
        'Toutes les annonces': 'كل الإعلانات', 'Mon espace': 'مساحتي',
        'Se connecter': 'تسجيل الدخول', 'Recherche rapide': 'بحث سريع',
        'Rechercher': 'بحث', 'Favoris': 'المفضلة', 'Ouvrir le menu': 'فتح القائمة',
        'Menu principal': 'القائمة الرئيسية', 'À propos': 'من نحن', 'Aide': 'المساعدة',
        'Questions fréquentes': 'الأسئلة الشائعة', 'Contactez-nous': 'اتصل بنا',
        'Types de biens': 'أنواع العقارات', 'Contact': 'اتصال', 'France': 'فرنسا',
        'Tous droits réservés.': 'جميع الحقوق محفوظة.', 'Mentions légales': 'الإشعارات القانونية',
        'Description du bien': 'وصف العقار', 'Ville': 'المدينة', 'Prix': 'السعر',
        'Téléphone': 'الهاتف', 'WhatsApp': 'واتساب', 'Publier': 'نشر', 'Envoyer': 'إرسال',
        'Suivant': 'التالي', 'Précédent': 'السابق', 'Annuler': 'إلغاء', 'Enregistrer': 'حفظ',
        'Oui': 'نعم', 'Non': 'لا', 'Chambres': 'غرف النوم', 'Salons': 'غرف الجلوس',
        'Salles de bains': 'الحمامات', 'Terrasse': 'التراس', 'Vue': 'الإطلالة',
        'Surface': 'المساحة', 'Étage': 'الطابق', 'Rechercher une ville': 'البحث عن مدينة',
        'Ajouter un mot personnel': 'إضافة كلمة شخصية', 'Un détail important, une précision sur le quartier ou vos conditions de visite…': 'تفصيل مهم أو توضيح عن الحي أو شروط الزيارة…', 'La description principale est déjà créée à partir de vos réponses. Cet espace vous permet d’ajouter un complément si vous le souhaitez.': 'تم إنشاء الوصف الرئيسي من إجاباتك. يمكنك إضافة تفاصيل هنا إذا رغبت.', 'Titre de l’annonce': 'عنوان الإعلان', 'Votre nom': 'اسمك', 'Votre numéro de téléphone': 'رقم هاتفك', 'Votre e-mail': 'بريدك الإلكتروني', 'Qui publie l’annonce ?': 'من ينشر الإعلان؟', 'Je publie mon propre bien.': 'أنشر عقاري الخاص.', 'Je publie pour un client.': 'أنشر لصالح عميل.', 'Les informations du bien': 'معلومات العقار', 'Type de bien': 'نوع العقار', 'Ville': 'المدينة', 'Quartier': 'الحي', 'Prix demandé': 'السعر المطلوب', 'Nombre de chambres': 'عدد غرف النوم', 'Nombre de salons': 'عدد غرف الجلوس', 'Nombre de salles de bains': 'عدد الحمامات', 'Superficie (m²)': 'المساحة (م²)', 'Superficie de la terrasse (m²)': 'مساحة التراس (م²)', 'Photos du bien': 'صور العقار', 'Continuer': 'متابعة', 'Retour': 'رجوع', 'Valider avec WhatsApp': 'التأكيد عبر واتساب', 'Vendre': 'بيع', 'Louer': 'كراء', 'Propriétaire': 'مالك', 'Terrasse': 'تراس', 'Ascenseur': 'مصعد', 'Garage ou sous-sol': 'مرآب أو قبو', 'Votre aperçu': 'معاينتك', 'Publier': 'نشر',
    },
    'en_US': {
        'Bien': 'Property', 'Caractéristiques': 'Features', 'Description': 'Description',
        'Contacter le vendeur': 'Contact seller', 'Déposer une annonce': 'Post a listing',
        'Toutes les annonces': 'All listings', 'Mon espace': 'My space',
        'Se connecter': 'Sign in', 'Recherche rapide': 'Quick search',
        'Rechercher': 'Search', 'Favoris': 'Favorites', 'Ouvrir le menu': 'Open menu',
        'Menu principal': 'Main menu', 'À propos': 'About', 'Aide': 'Help',
        'Questions fréquentes': 'Frequently asked questions', 'Contactez-nous': 'Contact us',
        'Types de biens': 'Property types', 'Contact': 'Contact', 'France': 'France',
        'Tous droits réservés.': 'All rights reserved.', 'Mentions légales': 'Legal notices',
        'Description du bien': 'Property description', 'Ville': 'City', 'Prix': 'Price',
        'Téléphone': 'Phone', 'WhatsApp': 'WhatsApp', 'Publier': 'Publish', 'Envoyer': 'Send',
        'Suivant': 'Next', 'Précédent': 'Previous', 'Annuler': 'Cancel', 'Enregistrer': 'Save',
        'Oui': 'Yes', 'Non': 'No', 'Chambres': 'Bedrooms', 'Salons': 'Living rooms',
        'Salles de bains': 'Bathrooms', 'Terrasse': 'Terrace', 'Vue': 'View',
        'Surface': 'Area', 'Étage': 'Floor', 'Rechercher une ville': 'Search a city',
        'Ajouter un mot personnel': 'Add a personal note', 'Un détail important, une précision sur le quartier ou vos conditions de visite…': 'An important detail, a note about the neighbourhood or your viewing terms…', 'La description principale est déjà créée à partir de vos réponses. Cet espace vous permet d’ajouter un complément si vous le souhaitez.': 'The main description is created from your answers. Add more details here if you wish.', 'Titre de l’annonce': 'Listing title', 'Votre nom': 'Your name', 'Votre numéro de téléphone': 'Your phone number', 'Votre e-mail': 'Your email', 'Qui publie l’annonce ?': 'Who is publishing the listing?', 'Je publie mon propre bien.': 'I am publishing my own property.', 'Je publie pour un client.': 'I am publishing for a client.', 'Les informations du bien': 'Property details', 'Type de bien': 'Property type', 'Ville': 'City', 'Quartier': 'Neighbourhood', 'Prix demandé': 'Asking price', 'Nombre de chambres': 'Number of bedrooms', 'Nombre de salons': 'Number of living rooms', 'Nombre de salles de bains': 'Number of bathrooms', 'Superficie (m²)': 'Area (m²)', 'Superficie de la terrasse (m²)': 'Terrace area (m²)', 'Photos du bien': 'Property photos', 'Continuer': 'Continue', 'Retour': 'Back', 'Valider avec WhatsApp': 'Confirm with WhatsApp', 'Vendre': 'For sale', 'Louer': 'For rent', 'Propriétaire': 'Owner', 'Terrasse': 'Terrace', 'Ascenseur': 'Lift', 'Garage ou sous-sol': 'Garage or basement', 'Votre aperçu': 'Your preview', 'Publier': 'Publish',
    },
}

def compile_mo(mapping, path):
    pairs = sorted(mapping.items())
    ids = [k.encode('utf-8') for k, _ in pairs]
    vals = [v.encode('utf-8') for _, v in pairs]
    n = len(pairs)
    off_orig = 28
    off_trans = off_orig + n * 8
    data_off = off_trans + n * 8
    orig_table = []
    trans_table = []
    blob = bytearray()
    for value in ids:
        orig_table.append((len(value), data_off + len(blob)))
        blob.extend(value + b'\0')
    for value in vals:
        trans_table.append((len(value), data_off + len(blob)))
        blob.extend(value + b'\0')
    out = bytearray(struct.pack('<7I', 0x950412de, 0, n, off_orig, off_trans, 0, 0))
    for length, offset in orig_table:
        out.extend(struct.pack('<2I', length, offset))
    for length, offset in trans_table:
        out.extend(struct.pack('<2I', length, offset))
    out.extend(blob)
    path.write_bytes(out)

for locale, mapping in translations.items():
    compile_mo(mapping, ROOT / f'{locale}.mo')
    (ROOT / f'{locale}.po').write_text('\n'.join([
        'msgid ""', 'msgstr ""', '"Project-Id-Version: Partikulier 6.17.0\\n"',
        '"Content-Type: text/plain; charset=UTF-8\\n"', f'"Language: {locale}\\n"', '',
    ] + [f'msgid "{k}"\nmsgstr "{v}"\n' for k, v in sorted(mapping.items())]), encoding='utf-8')
print('generated', ', '.join(f'{k}.mo' for k in translations))
