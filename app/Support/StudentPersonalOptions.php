<?php

declare(strict_types=1);

namespace App\Support;

final class StudentPersonalOptions
{
    public const DEFAULT_NATIONALITY = 'Ghanaian';

    /**
     * @return list<string>
     */
    public static function nationalities(): array
    {
        return [
            self::DEFAULT_NATIONALITY,
            'Algerian',
            'American',
            'Angolan',
            'Argentine',
            'Australian',
            'Bangladeshi',
            'Beninese',
            'Botswanan',
            'Brazilian',
            'British',
            'Burkinabé',
            'Burundian',
            'Cameroonian',
            'Canadian',
            'Cape Verdean',
            'Central African',
            'Chadian',
            'Chinese',
            'Comoran',
            'Congolese',
            'Danish',
            'Djiboutian',
            'Dutch',
            'Egyptian',
            'Equatorial Guinean',
            'Eritrean',
            'Eswatini',
            'Ethiopian',
            'Filipino',
            'Finnish',
            'French',
            'Gabonese',
            'Gambian',
            'German',
            'Guinean',
            'Bissau-Guinean',
            'Indian',
            'Indonesian',
            'Iranian',
            'Iraqi',
            'Irish',
            'Israeli',
            'Italian',
            'Ivorian',
            'Jamaican',
            'Japanese',
            'Jordanian',
            'Kenyan',
            'Kuwaiti',
            'Lebanese',
            'Lesothan',
            'Liberian',
            'Libyan',
            'Malagasy',
            'Malaysian',
            'Malian',
            'Mauritanian',
            'Mauritian',
            'Mexican',
            'Moroccan',
            'Mozambican',
            'Namibian',
            'Nepali',
            'New Zealander',
            'Nigerien',
            'Nigerian',
            'Norwegian',
            'Pakistani',
            'Polish',
            'Portuguese',
            'Qatari',
            'Russian',
            'Rwandan',
            'São Toméan',
            'Saudi',
            'Senegalese',
            'Seychellois',
            'Sierra Leonean',
            'Singaporean',
            'Somali',
            'South African',
            'South Korean',
            'South Sudanese',
            'Spanish',
            'Sri Lankan',
            'Sudanese',
            'Swedish',
            'Swiss',
            'Syrian',
            'Tanzanian',
            'Thai',
            'Togolese',
            'Trinidadian',
            'Tunisian',
            'Turkish',
            'Ugandan',
            'Ukrainian',
            'Emirati',
            'Vietnamese',
            'Zambian',
            'Zimbabwean',
            'Other',
        ];
    }

    /**
     * @return list<string>
     */
    public static function denominations(): array
    {
        return [
            'Catholic',
            'Methodist',
            'Presbyterian',
            'Anglican',
            'Pentecostal',
            'Charismatic',
            'Assemblies of God (A.G.)',
            'Baptist',
            'Evangelical Presbyterian',
            'Seventh-day Adventist',
            'Apostolic',
            'Salvation Army',
            'Lutheran',
            'Deeper Life',
            'Lighthouse Chapel',
            'International Central Gospel Church (ICGC)',
            'Christ Apostolic Church',
            'Jehovah\'s Witness',
            'Other',
        ];
    }

    /**
     * Preserve legacy or manually entered values when they are not in the list.
     *
     * @param  list<string>  $options
     * @return list<string>
     */
    public static function withCurrent(array $options, string $current): array
    {
        $current = trim($current);
        if ($current !== '' && ! in_array($current, $options, true)) {
            $options[] = $current;
        }

        return $options;
    }
}
