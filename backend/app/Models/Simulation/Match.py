from .Team import Team


class Match:
    """
    Basic class to define a match.
    A match consists of 2 teams.
    It can be a draw, or there can be a winner.
    One player from either team can be the player of the match.
    """

    def __init__(self, team1, team2):
        """
        Constructor.
        Args:
            team1 (Team): The first team.
            team2 (Team): The second team.
        """
        self.team1 = team1
        self.team2 = team2
        self.winner = None
        self.player_of_the_match = None

    def play(self):
        """
        Run the match.
        """
        self.distribute_points()
        self.determine_winner()
        self.determine_player_of_the_match()

    def distribute_points(self):
        """
        Abstract method to distribute points.
        Must be implemented in child classes.
        """
        pass

    def determine_winner(self):
        """
        Abstract method to determine the winner.
        Must be implemented in child classes.
        """
        pass

    def determine_player_of_the_match(self):
        """
        Abstract method to determine the player of the match.
        Must be implemented in child classes.
        """
        pass
