<div class="grid grid-cols-1 sm:grid-cols-2 <?= $is_student ? 'lg:grid-cols-3' : '' ?> gap-6">
    <!-- guardian name -->
    <div>
        <?= input(label: "Name", name: "name", value: $guardian["name"] ?? '', required: $is_student, attributes: placeholder("Guardian Name")) ?>
    </div>

    <!-- guardian relationship -->
        <div>
        <?= select("relationship", "Relationship", ["Father", "Mother", "Uncle", "Aunt", "Sibling", "Other"], true, required: $is_student, value: $guardian["relationship"] ?? ''); ?>
        </div>

        <!-- guardian address -->
    <div>
        <?= input(label: "Residence Address", name: "address", value: $guardian["address"] ?? '', attributes: placeholder("H.No 12, Atomic Street, East Legon, Accra, Greater Accra")); ?>
    </div>

    <!-- phone number -->
    <div>
        <?= input(label: "Phone Number", name: "phone_number", value: $guardian["phone_number"] ?? '', required: $is_student, attributes: placeholder("Guardian Phone Number")) ?>
    </div>

    <!-- email -->
    <div>
        <?= input('email', "Email", "email", $guardian["email"] ?? '', attributes: placeholder("Guardian Email Address")) ?>
    </div>
</div>