# Big-Two
PHP version of the Big Two card game
Original Release: December, 2002

Big Two, also known as Deuces and a plethora of other names (see http://en.wikipedia.org/wiki/Big_Two) is a fun card game that I enjoyed playing with my friend Alastair during high school. In 2002 during a visit we decided to start on writing a web-based implementation of this game.

Although we developed a functional game, we never got any further than that.

This implementation reflects the version of Big Two that we played, which may (likely) differ from other versions being played.

The deck is shuffled, and n cards are dealt to each player.

Players sort their hands and the player with the lowest card leads.

The player leading can play any valid card or combination (single, pair, three pair or a poker hand).

Subsequent players must play a higher ranking card or combination of the same type in play (eg, a pair can't be played on a single). If a player can't (or opts not to) play a card or combination, they pass.

When all players pass on a hand, the player of the last hand is declared the winner of that round, and they start the next round with whichever card or combination they prefer.

The first player to discard all of their cards wins the game.

Cards are ranked 3 4 5 6 7 8 9 10 J Q K A 2 and the suits are ranked D C H S (in ascending rank) - hence the name "big two".




