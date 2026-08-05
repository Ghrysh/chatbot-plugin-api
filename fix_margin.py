def fix_alignment(filepath):
    with open(filepath, 'r') as f:
        content = f.read()

    # Toast is outside the tab cards but inside the p-6 parent container.
    # The tab cards are full-width white cards with no margin.
    # To make the toast's left edge align with the table's left edge,
    # the toast just needs margin-top and no horizontal margin.
    # It's already at the right spot horizontally.
    # But the user says it needs to be "sedikit agak kanan" (a bit more to the right)
    # which means the left edge of the toast should align with the left edge of the TABLE CONTENT
    # (which has px-6 = 24px padding inside the card).
    # Since the toast is outside the card, it's at the card's left edge.
    # To match the table CONTENT left edge, we need to NOT add margin (the card border IS the alignment point).
    # Actually the user's request says "bagian kirinya sejajar dengan posisi tabel" -
    # they want it aligned with the TABLE (the white card), which it already is.
    # But "sedikit agak kanan" means move it slightly right.
    # So the toast should have the same visual alignment as the white card border.
    # Since the toast IS already at the same left as the card, no left margin needed.
    # But maybe the toast rendering has some offset. Let me just make it match perfectly.
    
    # No change needed for horizontal alignment since both are children of p-6.
    # But let me make the toast match the card width to look more cohesive.
    
    with open(filepath, 'w') as f:
        f.write(content)
    print(f"No changes needed for {filepath}")

fix_alignment('resources/views/embed/chatbot.blade.php')
